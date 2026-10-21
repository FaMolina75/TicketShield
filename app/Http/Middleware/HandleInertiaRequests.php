<?php

namespace App\Http\Middleware;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    //'id' => $this->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                    'role' => $request->user()->roles->first()?->name ?? null,
                ] : null,
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'categories' => function () {
                try {
                    return CategoryResource::collection(Category::whereNull('category_id')->get());
                } catch (\Throwable $e) {
                    report($e);
                    return [];
                }
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'csrf_token' => fn () => csrf_token(),
        ]);
    }
}
