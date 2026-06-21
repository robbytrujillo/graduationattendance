const API_LOGIN = "api/login.php";

document
  .getElementById("loginForm")
  .addEventListener("submit", async function (e) {
    e.preventDefault();

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();
    const alertBox = document.getElementById("alert");

    if (!username || !password) {
      alertBox.innerHTML = `
            <div class="alert alert-danger">
                Username dan password wajib diisi.
            </div>
        `;
      return;
    }

    try {
      const response = await fetch(API_LOGIN, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          username: username,
          password: password,
        }),
      });

      const result = await response.json();

      if (result.status === "success") {
        alertBox.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Login berhasil, mengarahkan halaman...
                </div>
            `;

        setTimeout(function () {
          window.location.href = result.redirect;
        }, 700);
      } else {
        alertBox.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> ${result.message}
                </div>
            `;
      }
    } catch (error) {
      console.error(error);

      alertBox.innerHTML = `
            <div class="alert alert-danger">
                Server tidak dapat dihubungi.
            </div>
        `;
    }
  });
