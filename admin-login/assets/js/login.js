document.addEventListener("DOMContentLoaded", function () {

    // -------------------------------------------------
    // 1. Tampilkan / sembunyikan password (semua tombol mata)
    // -------------------------------------------------
    document.querySelectorAll(".toggle-password").forEach(function (btn) {
        const input = document.getElementById(btn.dataset.target);
        if (!input) return;

        btn.addEventListener("click", function () {
            const hidden = input.type === "password";

            input.type = hidden ? "text" : "password";
            btn.textContent = hidden ? "🙈" : "👁";
            btn.setAttribute(
                "aria-label",
                hidden ? "Sembunyikan password" : "Tampilkan password"
            );
        });
    });

    // -------------------------------------------------
    // 2. Indikator kekuatan password (register / ganti password)
    // -------------------------------------------------
    const pw       = document.getElementById("password");
    const box      = document.getElementById("strength");
    const fill     = document.getElementById("strengthFill");
    const text     = document.getElementById("strengthText");
    const confirmI = document.getElementById("password_confirm");

    if (pw && box && fill && text) {
        pw.addEventListener("input", function () {
            const v = pw.value;
            let score = 0;

            if (v.length >= 8)  score++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
            if (/\d/.test(v))   score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;

            const levels = [
                { w: "10%",  c: "#dc2626", t: "Sangat lemah" },
                { w: "30%",  c: "#f97316", t: "Lemah" },
                { w: "55%",  c: "#eab308", t: "Cukup" },
                { w: "80%",  c: "#22c55e", t: "Kuat" },
                { w: "100%", c: "#059669", t: "Sangat kuat" }
            ];

            box.hidden = v.length === 0;
            fill.style.width      = levels[score].w;
            fill.style.background = levels[score].c;
            text.textContent      = "Kekuatan password: " + levels[score].t;
        });
    }

    // -------------------------------------------------
    // 3. Cek konfirmasi password langsung saat mengetik
    // -------------------------------------------------
    if (pw && confirmI) {
        const check = function () {
            confirmI.setCustomValidity(
                confirmI.value && confirmI.value !== pw.value
                    ? "Konfirmasi password tidak sama."
                    : ""
            );
        };
        pw.addEventListener("input", check);
        confirmI.addEventListener("input", check);
    }

    // -------------------------------------------------
    // 4. Konfirmasi sebelum aksi berbahaya (form[data-confirm])
    // -------------------------------------------------
    document.querySelectorAll("form[data-confirm]").forEach(function (form) {
        form.addEventListener("submit", function (ev) {
            if (!window.confirm(form.dataset.confirm)) {
                ev.preventDefault();
            }
        });
    });
});
