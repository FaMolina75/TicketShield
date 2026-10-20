import React, { useEffect, useRef, useState } from "react";
import Button from "@/Components/Button";
import Guest from "@/Layouts/Guest";
import Input from "@/Components/Input";
import Label from "@/Components/Label";
import ValidationErrors from "@/Components/ValidationErrors";
import InputError from "@/Components/InputError";
import { Head, Link, useForm } from "@inertiajs/react";

export default function Register({ recaptchaSiteKey }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        "g-recaptcha-response": "",
    });

    const captchaContainer = useRef(null);
    const captchaWidgetId = useRef(null);
    const setDataRef = useRef(setData);
    setDataRef.current = setData;

    const [captchaError, setCaptchaError] = useState("");

    useEffect(() => {
        return () => {
            reset("password", "password_confirmation");
        };
    }, []);

    useEffect(() => {
        if (!recaptchaSiteKey) {
            return;
        }

        let cancelled = false;

        const renderCaptcha = () => {
            if (
                !cancelled &&
                captchaContainer.current &&
                window.grecaptcha &&
                captchaWidgetId.current === null
            ) {
                captchaWidgetId.current = window.grecaptcha.render(
                    captchaContainer.current,
                    {
                        sitekey: recaptchaSiteKey,

                        callback: (token) => {
                            setCaptchaError("");
                            setDataRef.current(
                                "g-recaptcha-response",
                                token
                            );
                        },

                        "expired-callback": () => {
                            setDataRef.current(
                                "g-recaptcha-response",
                                ""
                            );
                        },

                        "error-callback": () => {
                            setDataRef.current(
                                "g-recaptcha-response",
                                ""
                            );

                            setCaptchaError(
                                "No se pudo verificar reCAPTCHA. Inténtalo nuevamente."
                            );
                        },
                    }
                );
            }
        };

        const scriptId = "google-recaptcha-script";
        let script = document.getElementById(scriptId);

        const handleScriptLoad = () => {
            if (window.grecaptcha) {
                window.grecaptcha.ready(renderCaptcha);
            }
        };

        const handleScriptError = () => {
            setCaptchaError(
                "No se pudo cargar reCAPTCHA. Revisa tu conexión."
            );
        };

        if (!script) {
            script = document.createElement("script");
            script.id = scriptId;
            script.src =
                "https://www.google.com/recaptcha/api.js?render=explicit";
            script.async = true;
            script.defer = true;

            script.addEventListener("load", handleScriptLoad);
            script.addEventListener("error", handleScriptError);

            document.head.appendChild(script);
        } else if (window.grecaptcha) {
            handleScriptLoad();
        } else {
            script.addEventListener("load", handleScriptLoad);
            script.addEventListener("error", handleScriptError);
        }

        return () => {
            cancelled = true;

            script.removeEventListener("load", handleScriptLoad);
            script.removeEventListener("error", handleScriptError);
        };
    }, [recaptchaSiteKey]);

    const onHandleChange = (event) => {
        setData(
            event.target.name,
            event.target.type === "checkbox"
                ? event.target.checked
                : event.target.value
        );
    };

    const submit = (e) => {
        e.preventDefault();

        if (!recaptchaSiteKey || !data["g-recaptcha-response"]) {
            setCaptchaError(
                "Completa la verificación reCAPTCHA para registrarte."
            );
            return;
        }

        post(route("register"), {
            onError: () => {
                setData("g-recaptcha-response", "");

                if (
                    captchaWidgetId.current !== null &&
                    window.grecaptcha
                ) {
                    window.grecaptcha.reset(captchaWidgetId.current);
                }
            },
        });
    };

    return (
        <Guest>
            <Head title="Registro" />

            <ValidationErrors errors={errors} />

            <form onSubmit={submit}>
                <div>
                    <Label forInput="name" value="Nombre" />

                    <Input
                        type="text"
                        name="name"
                        value={data.name}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        handleChange={onHandleChange}
                        required
                    />
                </div>

                <div className="mt-4">
                    <Label forInput="email" value="Correo" />

                    <Input
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        handleChange={onHandleChange}
                        required
                    />
                </div>

                <div className="mt-4">
                    <Label forInput="password" value="Contraseña" />

                    <Input
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        handleChange={onHandleChange}
                        required
                    />
                </div>

                <div className="mt-4">
                    <Label
                        forInput="password_confirmation"
                        value="Confirmar contraseña"
                    />

                    <Input
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        handleChange={onHandleChange}
                        required
                    />
                </div>

                {/* Google reCAPTCHA */}
                <div className="mt-6">
                    {recaptchaSiteKey ? (
                        <>
                            <div ref={captchaContainer} />

                            <InputError
                                message={
                                    errors["g-recaptcha-response"]
                                }
                                className="mt-2"
                            />

                            {captchaError && (
                                <p className="mt-2 text-sm text-red-600">
                                    {captchaError}
                                </p>
                            )}
                        </>
                    ) : (
                        <p className="text-sm text-red-600">
                            reCAPTCHA no está configurado.
                            Contacta al administrador.
                        </p>
                    )}
                </div>

                <div className="flex items-center justify-end mt-8">
                    <Link
                        href={route("login")}
                        className="underline text-sm hover:text-gray-200"
                    >
                        ¿Ya estás registrado?
                    </Link>

                    <Button
                        className="ml-4"
                        processing={processing}
                        disabled={
                            processing ||
                            !recaptchaSiteKey ||
                            !data["g-recaptcha-response"]
                        }
                    >
                        Registrar
                    </Button>
                </div>
            </form>
        </Guest>
    );
}