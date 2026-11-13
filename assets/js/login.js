
document.addEventListener("DOMContentLoaded", function () {
    const senha = document.querySelector("#senha");
    const passwordToggle = document.querySelector(".password-toggle");

    if (!senha || !passwordToggle) return;

    passwordToggle.addEventListener("click", function () {
        const mostrarSenha = senha.type === "password";

        senha.type = mostrarSenha ? "text" : "password";

        passwordToggle.innerHTML = mostrarSenha
            ? '<i class="bi bi-eye-slash"></i>'
            : '<i class="bi bi-eye"></i>';

        passwordToggle.setAttribute(
            "aria-label",
            mostrarSenha ? "Ocultar senha" : "Mostrar senha"
        );
    });
});
