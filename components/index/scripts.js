document.addEventListener("DOMContentLoaded", () => {
    const loginModal = document.getElementById("login-modal");
    const registerModal = document.getElementById("register-modal");

    const openLoginBtn = document.querySelectorAll("#open-login");
    const openRegisterBtn = document.getElementById("open-register");

    const closeLoginBtn = document.getElementById("close-login");
    const closeRegisterBtn = document.getElementById("close-register");

    openLoginBtn.forEach(btn => {
        btn.addEventListener("click", () => {
            loginModal.classList.add("active");
        });
    });

    openRegisterBtn.addEventListener("click", () => {
        registerModal.classList.add("active");
    });

    closeLoginBtn.addEventListener("click", () => {
        loginModal.classList.remove("active");
    });

    closeRegisterBtn.addEventListener("click", () => {
        registerModal.classList.remove("active");
    });

    window.addEventListener("click", (event) => {
        if (event.target === loginModal) {
            loginModal.classList.remove("active");
        }
        if (event.target === registerModal) {
            registerModal.classList.remove("active");
        }
    });
});
