from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait

URL = "https://ticketshield-ihy6.onrender.com/login"

driver = None

try:
    print("Iniciando prueba de seguridad de TicketShield...")

    driver = webdriver.Chrome()
    driver.set_window_size(1280, 900)
    driver.get(URL)

    wait = WebDriverWait(driver, 90)

    # Esperar al formulario de inicio de sesión.
    email = wait.until(
        lambda d: d.find_element(By.CSS_SELECTOR, 'input[name="email"]')
    )

    password = driver.find_element(
        By.CSS_SELECTOR,
        'input[name="password"]'
    )

    print("✅ Campo de correo detectado.")
    print("✅ Campo de contraseña detectado.")

    # Esperar a que Google reCAPTCHA termine de renderizarse.
    wait.until(
        lambda d: len(
            d.find_elements(
                By.CSS_SELECTOR,
                'iframe[src*="recaptcha"]'
            )
        ) > 0
    )

    print("✅ Google reCAPTCHA detectado.")

    # Comprobar que el botón de inicio de sesión está bloqueado.
    boton = driver.find_element(
        By.CSS_SELECTOR,
        'button[type="submit"]'
    )

    assert not boton.is_enabled(), (
        "El botón de inicio de sesión debería estar deshabilitado "
        "antes de completar reCAPTCHA."
    )

    print("✅ Botón bloqueado sin completar reCAPTCHA.")

    assert email.is_displayed()
    assert password.is_displayed()

    print("\n🎉 PRUEBA APROBADA: Login protegido con reCAPTCHA.")

except Exception as error:
    print("\n❌ PRUEBA FALLIDA:", type(error).__name__)
    print(str(error))
    raise

finally:
    if driver is not None:
        driver.quit()
