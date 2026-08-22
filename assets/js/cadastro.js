document.addEventListener("DOMContentLoaded", function () {

    const form = document.querySelector(".cadastro-form");


    form.addEventListener("submit", function (event) {

        event.preventDefault();


        // ===========================
        // PEGAR DADOS DO FORMULÁRIO
        // ===========================

        const nome = document.getElementById("nome").value.trim();
        const email = document.getElementById("email").value.trim().toLowerCase();
        const senha = document.getElementById("senha").value;
        const confirmarSenha =
            document.getElementById("confirmar-senha").value;

        const tipoConta = document.querySelector(
            'input[name="tipo-conta"]:checked'
        ).value;


        // ===========================
        // VERIFICAR SENHAS
        // ===========================

        if (senha !== confirmarSenha) {

            alert("As senhas não coincidem.");

            return;
        }


        // ===========================
        // BUSCAR CONTAS EXISTENTES
        // ===========================

        let contas =
            JSON.parse(localStorage.getItem("bsyncContas")) || [];


        // ===========================
        // VERIFICAR E-MAIL EXISTENTE
        // ===========================

        const emailExiste = contas.some(function (conta) {

            return conta.email === email;

        });


        if (emailExiste) {

            alert("Este e-mail já está cadastrado.");

            return;
        }


        // ===========================
        // CRIAR NOVA CONTA
        // ===========================

        const novaConta = {

            nome: nome,
            email: email,
            senha: senha,
            tipo: tipoConta

        };


        // ===========================
        // ADICIONAR À LISTA
        // ===========================

        contas.push(novaConta);


        // ===========================
        // SALVAR
        // ===========================

        localStorage.setItem(
            "bsyncContas",
            JSON.stringify(contas)
        );


        alert("Conta criada com sucesso!");


        // Voltar para o login
        window.location.href = "login.html";

    });

});