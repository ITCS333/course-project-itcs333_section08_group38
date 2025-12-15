const SESSION_URL = "../auth/api/session.php";

function getReturnTo() {
  const path = window.location.pathname + window.location.search;
  return encodeURIComponent(path);
}

export async function requireLogin() {
  try {
    const res = await fetch(SESSION_URL, { method: "POST", credentials: "include" });
    const data = await res.json();

    if (!data.logged_in) {
      window.location.href = "../auth/login.html?returnTo=" + getReturnTo();
    }
  } catch {
    window.location.href = "../auth/login.html?returnTo=" + getReturnTo();
  }
}

export async function requireAdmin() {
  try {
    const res = await fetch(SESSION_URL, { method: "POST", credentials: "include" });
    const data = await res.json();

    if (!data.logged_in) {
      window.location.href = "../auth/login.html?returnTo=" + getReturnTo();
      return;
    }

    if (data.user?.role !== "admin") {
      window.location.href = "../../index.html";
    }
  } catch {
    window.location.href = "../auth/login.html?returnTo=" + getReturnTo();
  }
}