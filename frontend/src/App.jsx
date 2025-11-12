// src/App.jsx
import React, { useCallback } from "react";
import { BrowserRouter, Routes, Route, useNavigate, useLocation, Navigate } from "react-router-dom";

// admin components
import Header from "./components/Header";
import Sidebar from "./components/Sidebar";
import Students from "./pages/admin/Students";
import Wali from "./pages/admin/Wali";
import Kategori from "./pages/admin/Kategori";
import Kelas from "./pages/admin/Kelas";
import Payments from "./pages/admin/Payments";
import Report from "./pages/admin/Report";
import DashboardAdmin from "./pages/admin/DashboardAdmin";
import Settings from "./pages/admin/Settings";

// auth / public
import Login from "./pages/Login";

// parent (user) pages
import DashboardUser from "./pages/parent/DashboardUser";
import Tagihan from "./pages/parent/Tagihan";
import Riwayat from "./pages/parent/Riwayat";
import Profil from "./pages/parent/Profil";

/**
 * Simple helper to translate pathname -> sidebar active id
 * (you can extend for parent routes if you want different ids)
 */
const pathToId = (path) => {
  if (path === "/" || path === "") return "dashboard";
  
  // master routes
  if (path.startsWith("/master/students")) return "students";
  if (path.startsWith("/master/wali")) return "wali";
  if (path.startsWith("/master/kategori")) return "kategori";
  if (path.startsWith("/master/kelas")) return "kelas";
  if (path.startsWith("/master")) return "master";

  // transaksi routes
  if (path.startsWith("/transaksi/jenis_tagihan")) return "jenis_tagihan";
  if (path.startsWith("/transaksi/tagihan")) return "tagihan";
  if (path.startsWith("/transaksi/pembayaran")) return "pembayaran";
  if (path.startsWith("/transaksi/pengeluaran")) return "pengeluaran";
  if (path.startsWith("/transaksi")) return "transaksi";

  // laporan routes
  if (path.startsWith("/laporan/kas_harian")) return "kas_harian";
  if (path.startsWith("/laporan/rekap_bulanan")) return "rekap_bulanan";
  if (path.startsWith("/laporan")) return "laporan";

  if (path.startsWith("/settings")) return "settings";

  // parent mappings
  if (path.startsWith("/parent/tagihan")) return "tagihan";
  if (path.startsWith("/parent/riwayat")) return "riwayat";
  if (path.startsWith("/parent/profil")) return "profil";
  if (path.startsWith("/parent")) return "dashboard";

  return "dashboard";
};

function RequireParentAuth({ children }) {
  const role = localStorage.getItem("role");
  if (role === "parent") {
    return children;
  }
  return <Navigate to="/login" replace />;
}

/** App layout used for admin & parent screens (shares Header/Sidebar/Footer) */
function AppLayout() {
  const navigate = useNavigate();
  const location = useLocation();

  const active = pathToId(location.pathname);
  const role = localStorage.getItem("role") || "admin";

  const setActive = useCallback(
    (id) => {
      if (role === "admin") {
        switch (id) {
          case "dashboard":
            navigate("/");
            break;
          case "students":
            navigate("/master/students");
            break;
          case "wali":
            navigate("/master/wali");
            break;
          case "kategori":
            navigate("/master/kategori");
            break;
          case "kelas":
            navigate("/master/kelas");
            break;
          case "jenis_tagihan":
            navigate("/transaksi/jenis_tagihan");
            break;
          case "tagihan":
            navigate("/transaksi/tagihan");
            break;
          case "pembayaran":
            navigate("/transaksi/pembayaran");
            break;
          case "pengeluaran":
            navigate("/transaksi/pengeluaran");
            break;
          case "kas_harian":
            navigate("/laporan/kas_harian");
            break;
          case "rekap_bulanan":
            navigate("/laporan/rekap_bulanan");
            break;
          case "settings":
            navigate("/settings");
            break;
          default:
            navigate("/");
        }
      } else {
        switch (id) {
          case "dashboard":
            navigate("/parent");
            break;
          case "tagihan":
            navigate("/parent/tagihan");
            break;
          case "riwayat":
            navigate("/parent/riwayat");
            break;
          case "profil":
            navigate("/parent/profil");
            break;
          default:
            navigate("/parent");
        }
      }
    },
    [navigate, role]
  );

  function handleLogout() {
    // demo logout: clear localStorage and go to login
    localStorage.removeItem("role");
    localStorage.removeItem("auth");
    navigate("/login", { replace: true });
  }

  return (
    <div className="min-h-screen bg-gray-50 font-sans">
      <Header onLogout={handleLogout} />
      <Sidebar active={active} setActive={setActive} />

      {/* main assumes sidebar is always visible (md:ml-64) */}
      <main className="ml-0 md:ml-64 pt-20 pb-12">
        {/* gunakan full width tanpa mx-auto/max-w untuk meminimalkan space sisi */}
        <div className="w-full px-4">
          <Routes>
            <Route path="/" element={<DashboardAdmin />} />
            
            {/* master routes - now using correct components */}
            <Route path="/master/students" element={<Students />} />
            <Route path="/master/wali" element={<Wali />} />
            <Route path="/master/kategori" element={<Kategori />} />
            <Route path="/master/kelas" element={<Kelas />} />

            {/* transaksi routes */}
            <Route path="/transaksi/jenis_tagihan" element={<Students />} />
            <Route path="/transaksi/tagihan" element={<Students />} />
            <Route path="/transaksi/pembayaran" element={<Payments />} />
            <Route path="/transaksi/pengeluaran" element={<Students />} />

            {/* laporan routes */}
            <Route path="/laporan/kas_harian" element={<Report />} />
            <Route path="/laporan/rekap_bulanan" element={<Report />} />

            <Route path="/settings" element={<Settings />} />

            {/* parent routes */}
            <Route path="/parent" element={<RequireParentAuth><DashboardUser /></RequireParentAuth>} />
            <Route path="/parent/tagihan" element={<RequireParentAuth><Tagihan /></RequireParentAuth>} />
            <Route path="/parent/riwayat" element={<RequireParentAuth><Riwayat /></RequireParentAuth>} />
            <Route path="/parent/profil" element={<RequireParentAuth><Profil /></RequireParentAuth>} />

            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </div>
      </main>
    </div>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <AppLayout />
    </BrowserRouter>
  );
}
