import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import { FaGraduationCap, FaSignInAlt, FaPhone, FaUser } from "react-icons/fa";

export default function Login() {
  const navigate = useNavigate();
  const [nis, setNis] = useState("");
  const [phone, setPhone] = useState("");
  const [remember, setRemember] = useState(false);

  function handleSubmit(e) {
    e.preventDefault();

    // Demo validation (replace with real auth)
    if (nis === "2023001" && phone === "081234567890") {
      // in a real app: save token / set auth state
      navigate("/parent", { replace: true });
    } else {
      alert("NIS atau nomor telepon tidak valid. Silakan coba lagi.");
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-[#667eea] to-[#764ba2]">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <div className="h-16 w-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
            <FaGraduationCap className="text-3xl text-blue-600" />
          </div>
          <h1 className="text-2xl font-bold text-white mb-2">SPP School</h1>
          <p className="text-blue-100">Sistem Informasi Pembayaran SPP</p>
        </div>

        <div className="bg-white rounded-lg shadow-xl p-8">
          <div className="text-center mb-6">
            <h2 className="text-xl font-semibold text-gray-900 mb-2">Login Wali Murid</h2>
            <p className="text-sm text-gray-600">Masuk untuk cek tagihan SPP dan status pembayaran</p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6" aria-label="Form Login Wali Murid">
            <div>
              <label htmlFor="nis" className="block text-sm font-medium text-gray-700 mb-2">Nomor Induk Siswa (NIS)</label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                  <FaUser />
                </div>
                <input
                  id="nis"
                  name="nis"
                  value={nis}
                  onChange={(e) => setNis(e.target.value)}
                  required
                  className="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Masukkan NIS siswa"
                />
              </div>
            </div>

            <div>
              <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-2">Nomor Telepon Wali</label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                  <FaPhone />
                </div>
                <input
                  id="phone"
                  name="phone"
                  type="tel"
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  required
                  className="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Masukkan nomor telepon wali"
                />
              </div>
            </div>

            <div className="flex items-center justify-between">
              <label className="flex items-center text-sm">
                <input type="checkbox" checked={remember} onChange={() => setRemember(!remember)} className="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                <span className="ml-2 text-sm text-gray-600">Ingat saya</span>
              </label>

              <button type="button" onClick={() => alert("Fitur lupa password belum tersedia.")} className="text-sm text-blue-600 hover:text-blue-800">Lupa password?</button>
            </div>

            <button type="submit" className="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2">
              <FaSignInAlt />
              <span>Login</span>
            </button>
          </form>

          <div className="mt-6 pt-6 border-t border-gray-200 text-center">
            <p className="text-sm text-gray-600 mb-2">Login sebagai admin?</p>
            <button onClick={() => navigate("/")} className="text-sm text-blue-600 hover:text-blue-800 font-medium">Masuk ke Dashboard Admin</button>
          </div>
        </div>

        <div className="text-center mt-8">
          <p className="text-blue-100 text-sm">© 2025 Marshell Fitriawan. All rights reserved.</p>
        </div>
      </div>
    </div>
  );
}
