(async function () {
  try {
    const res = await fetch("../auth/api/auth_check.php", { credentials: "include" });
    const data = await res.json();

    if (!data.logged_in) {
      window.location.href = "../auth/login.html";
    }
  } catch (e) {
    window.location.href = "../auth/login.html";
  }
})();
