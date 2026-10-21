import React from 'react';
import Button from '@/Components/Button';
import Guest from '@/Layouts/Guest';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
	const { post, processing } = useForm();

	const submit = (e) => {
		e.preventDefault();

		post(route('verification.send'));
	};

	return (
		<Guest>
			<Head title="Email Verification" />

			<div className="mb-4 text-sm ">
				¡Gracias por registrarte! Antes de comenzar, por favor verifica tu correo electrónico haciendo clic en el enlace que te enviamos. Si no has recibido el correo o estás en entorno de demostración, haz clic en el botón para continuar.
			</div>

			{status === 'verification-link-sent' && (
				<div className="mb-4 font-medium text-sm text-green-600">
					Se ha procesado la verificación de tu cuenta.
				</div>
			)}

			<form onSubmit={submit}>
				<div className="mt-4 flex items-center justify-between">
					<Button processing={processing}>Verificar o Reenviar Correo</Button>

					<Link
						href={route('logout')}
						method="post"
						as="button"
						className="underline text-sm hover:text-gray-200"
					>
						Cerrar sesión
					</Link>
				</div>
			</form>
		</Guest>
	);
}
