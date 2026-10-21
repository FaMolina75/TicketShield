import React from 'react';
import Button from '@/Components/Button';
import Guest from '@/Layouts/Guest';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
	const { post, processing } = useForm();
	const instantForm = useForm();

	const submit = (e) => {
		e.preventDefault();
		post(route('verification.send'));
	};

	const verifyInstant = (e) => {
		e.preventDefault();
		instantForm.post(route('verification.instant'));
	};

	return (
		<Guest>
			<Head title="Verificación de Correo" />

			<div className="mb-4 text-sm text-gray-600">
				¡Gracias por registrarte! Te hemos enviado un correo de verificación con un enlace seguro. Por favor revisa tu bandeja de entrada o tu carpeta de <strong>SPAM / Correo no deseado</strong>.
			</div>

			{status === 'verification-link-sent' && (
				<div className="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded-lg border border-green-200">
					✓ Se ha enviado un nuevo enlace de verificación a tu correo. Revisa también tu carpeta de SPAM.
				</div>
			)}

			<div className="space-y-3 mt-6">
				<form onSubmit={verifyInstant}>
					<Button
						className="w-full justify-center bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5"
						processing={instantForm.processing}
					>
						✓ Verificar mi cuenta ahora y continuar
					</Button>
				</form>

				<form onSubmit={submit}>
					<button
						type="submit"
						disabled={processing}
						className="w-full text-center py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none"
					>
						{processing ? 'Enviando correo...' : 'Reenviar correo con Brevo'}
					</button>
				</form>

				<div className="pt-2 text-center">
					<Link
						href={route('logout')}
						method="post"
						as="button"
						className="underline text-sm text-gray-500 hover:text-gray-700"
					>
						Cerrar sesión
					</Link>
				</div>
			</div>
		</Guest>
	);
}
