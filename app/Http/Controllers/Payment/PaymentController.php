<?php

namespace App\Http\Controllers\Payment;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Event;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
	public function payment(PaymentRequest $request)
	{
		$event = Event::with('sessions_available.ticket_types_available', 'promotions', 'location')
			->findOrFail($request->event_id);

		$session_selected = $event->sessions_available->firstWhere('date', $request->date);

		if (!$session_selected) {
			return back()->withErrors(['date' => 'La fecha o sesión seleccionada no está disponible.']);
		}

		$tickets = $session_selected->ticket_types_available;

		if ($request->code_promotion) {
			$promotion = $event->promotions_available()->active()->where('code', $request->code_promotion)->first();
			if (!$promotion) {
				return back()->withErrors(['code_promotion' => 'El código de promoción no es válido o ha expirado.']);
			}
		} else {
			$promotion = null;
		}

		$tickets_selected = OrderService::calculatePriceTicket(
			$tickets,
			$request->input('tickets_quantity', [])
		);

		if ($tickets_selected->isEmpty()) {
			return back()->withErrors(['tickets_quantity' => 'Debe seleccionar al menos un boleto válido.']);
		}

		try {
			$order = DB::transaction(function () use ($event, $session_selected, $tickets_selected, $promotion, $request) {
				// Validar stock y límites de compra con bloqueo pesimista
				foreach ($tickets_selected as $item) {
					if ($item->quantity_selected < $item->min_purchase || $item->quantity_selected > $item->max_purchase) {
						throw new \DomainException("La cantidad seleccionada para '{$item->name}' debe estar entre {$item->min_purchase} y {$item->max_purchase}.");
					}

					$pivot = DB::table('session_ticket_type')
						->where('session_id', $session_selected->id)
						->where('ticket_type_id', $item->id)
						->lockForUpdate()
						->first();

					$currentSales = $pivot ? (int)$pivot->sales : 0;
					$available = $item->quantity - $currentSales;

					if ($item->quantity_selected > $available) {
						throw new \DomainException("No hay suficiente disponibilidad para el boleto '{$item->name}'. Disponibles: {$available}.");
					}
				}

				$order = OrderService::createOrder($event, $session_selected, $tickets_selected, $promotion);

				$order->data = array_merge(
					(array)$order->data,
					[
						'user' => [
							'name' => $request->name,
							'phone' => $request->phone,
							'email' => auth()->user()->email,
						]
					]
				);

				if ($order->total > 0) {
					// Nota de auditoría: Pasarela de pagos simulada para entorno de demostración
					$order->stripe_id = 'SIMULATED_' . Str::random(24);
				} else {
					$order->stripe_id = '';
				}

				$order->status = OrderStatus::SUCCESSFUL;
				$order->save();

				$tickets_payments = [];
				foreach ($tickets_selected as $key => $item) {
					$tickets_payments[$key] = [
						'name' => $item->name,
						'price' => $item->price,
						'quantity' => $item->quantity_selected,
						'total' => $item->price_quantity,
						'ticket_type_id' => $item->id,
						'event_id' => $event->id,
						'user_id' => auth()->user()->id,
					];
				}

				$order->order_tickets()->createMany($tickets_payments);

				return $order;
			});
		} catch (\DomainException $e) {
			return back()->withErrors(['message' => $e->getMessage()]);
		} catch (\Throwable $e) {
			report($e);
			return back()->withErrors(['message' => 'Al parecer hubo un error! El pago a través de su tarjeta no se pudo realizar.']);
		}

		return Redirect::route('profile.order_details', ['code' => $order->code])
			->with(
				'success',
				'Orden completada con exito'
			);
	}
}
