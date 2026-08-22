document.addEventListener("DOMContentLoaded", function () {

    const form = document.querySelector(".login-form");
    const email = document.querySelector("#email");
    const senha = document.querySelector("#senha");
    const passwordToggle = document.querySelector(".password-toggle");


    // ===========================
    // USUÁRIOS DE TESTE
    // ===========================

    const usuarios = {

        "admin@bsync.com": {
            senha: "123456",
            tipo: "admin",
            pagina: "../admin/dashboard.html"
        },

        "usuario@bsync.com": {
            senha: "123456",
            tipo: "usuario",
            pagina: "dashboard.html"
        }

    };


    // ===========================
    // MOSTRAR / OCULTAR SENHA
    // ===========================

    passwordToggle.addEventListener("click", function () {

        if (senha.type === "password") {

            senha.type = "text";

            passwordToggle.innerHTML =
                '<i class="bi bi-eye-slash"></i>';

            passwordToggle.setAttribute(
                "aria-label",
                "Ocultar senha"
            );

        } else {

            senha.type = "password";

            passwordToggle.innerHTML =
                '<i class="bi bi-eye"></i>';

            passwordToggle.setAttribute(
                "aria-label",
                "Mostrar senha"
            );
        }

    });


    // ===========================
    // LOGIN
    // ===========================

    form.addEventListener("submit", function (event) {

        event.preventDefault();

        const emailDigitado = email.value.trim().toLowerCase();
        const senhaDigitada = senha.value;


       // ===========================
// VERIFICA CONTAS CADASTRADAS
// ===========================

const contasSalvas =
    JSON.parse(localStorage.getItem("bsyncContas")) || [];


const conta = contasSalvas.find(function (conta) {

    return conta.email === emailDigitado;

});


if (conta) {

    if (conta.senha !== senhaDigitada) {

        alert("E-mail ou senha incorretos.");

        return;
    }


    // Salva o usuário logado
    localStorage.setItem(
        "usuarioLogado",
        emailDigitado
    );

    localStorage.setItem(
        "bsyncUsuarioLogado",
        JSON.stringify(conta)
    );


    // Administrador
    if (conta.tipo === "admin") {

        window.location.href =
            "../admin/dashboard.html";

    }

    // Usuário
    else {

        window.location.href =
            "dashboard.html";

    }

    return;
}


        // ===========================
        // VERIFICA USUÁRIOS DE TESTE
        // ===========================

        const usuario = usuarios[emailDigitado];


        if (!usuario) {

            alert("E-mail ou senha incorretos.");
            return;

        }


        if (usuario.senha !== senhaDigitada) {

            alert("E-mail ou senha incorretos.");
            return;

        }


        // Salva o perfil
        localStorage.setItem(
            "usuarioLogado",
            emailDigitado
        );


        // Redireciona
        window.location.href = usuario.pagina;

    });

});