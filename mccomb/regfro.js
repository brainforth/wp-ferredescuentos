document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("regForm");
    const submitBtn = document.getElementById("submitBtn");
    const terminos = document.getElementById("terminos");
    const loader = document.createElement("div");
    const messageDiv = document.createElement("div");

    loader.className = "spinner";
    loader.style.display = "none";
    messageDiv.style.marginTop = "10px";

    form.parentNode.appendChild(messageDiv);

    const inputs = {
        nombre: document.getElementById("nombre"),
        apellidos: document.getElementById("apellidos"),
        correo: document.getElementById("correo"),
        telefono: document.getElementById("telefono"),
    };

    const validators = {
        nombre: (value) => /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(value) && value.length <= 52,
        apellidos: (value) => /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(value) && value.length <= 52,
        correo: (value) => /^[a-zA-Z0-9._@-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value) && value.length <= 52,
        telefono: (value) => /^\d+$/.test(value) && value.length <= 15,
    };

    const restrictions = {
        nombre: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]*$/,
        apellidos: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]*$/,
        correo: /^[a-zA-Z0-9._@-]*$/,
        telefono: /^\d*$/,
    };

    for (const [key, input] of Object.entries(inputs)) {
        input.addEventListener("input", () => {
            const value = input.value.trim();
            if (!restrictions[key].test(value) || value.length > (key === "telefono" ? 15 : 52)) {
                input.value = value.slice(0, -1);
            }
        });
    }

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        if (!terminos.checked) {
            alert("Debe aceptar los términos y condiciones.");
            return;
        }

        let allValid = true;
        for (const [key, input] of Object.entries(inputs)) {
            const value = input.value.trim();
            if (!validators[key](value)) {
                allValid = false;
                alert(`El campo ${key} no es válido. Verifique su información.`);
                input.focus();
                break;
            }
        }

        if (!allValid) return;

        const recaptchaToken = await grecaptcha.execute('6LdtGr8qAAAAANhCW-WZ-CJf9BfV9-YHRRNYSnS3', { action: 'submit' });

        submitBtn.remove();
        form.appendChild(loader);
        loader.style.display = "block";

        const formData = {
            nombre: inputs.nombre.value.trim(),
            apellidos: inputs.apellidos.value.trim(),
            correo: inputs.correo.value.trim(),
            telefono: inputs.telefono.value.trim(),
            recaptchaToken,
        };

        try {
            const response = await fetch("https://ferredescuentos.com/mccomb/regcu.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(formData),
            });

            const result = await response.json();

            messageDiv.className = "";
            loader.style.display = "none";

            if (response.ok) {
                form.remove();
                messageDiv.className = "succmsg";

                // Crear los elementos h3 y p para el mensaje
                const h3 = document.createElement("h3");
                h3.textContent = "Suscripción exitosa.";
                const p = document.createElement("p");
                p.textContent = "Pronto comenzarás a recibir nuestras promociones.";

                messageDiv.appendChild(h3);
                messageDiv.appendChild(p);
            } else if (response.status === 400 && result.message.includes("Ya estás suscrito")) {
                messageDiv.className = "errmsg";
                messageDiv.textContent = result.message;
                if (!submitBtn.parentNode) form.appendChild(submitBtn);
            } else {
                throw new Error(result.message || "Algo salió mal");
            }
        } catch (error) {
            loader.style.display = "none";
            messageDiv.className = "errmsg";
            messageDiv.textContent = error.message;
            if (!submitBtn.parentNode) form.appendChild(submitBtn);
        }
    });
});
