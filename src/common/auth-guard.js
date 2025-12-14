const AUTH_CHECK_URL = "../auth/api/check.php";  

async function requireLogin() {
  try {
    const res = await fetch(AUTH_CHECK_URL, { credentials: "include" });
    const data = await res.json();

    if (!data.logged_in) {
      const current = window.location.pathname + window.location.search;
      window.location.href =
        "../auth/login.html?returnTo=" + encodeURIComponent(current);

    }
  } catch (e) {
   
    window.location.href = "../auth/login.html";
  }
}

document.addEventListener("DOMContentLoaded", requireLogin);
