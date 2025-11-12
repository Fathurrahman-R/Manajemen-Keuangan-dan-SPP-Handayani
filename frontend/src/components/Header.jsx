import React from "react";
import { FaGraduationCap, FaSignOutAlt } from "react-icons/fa";

export default function Header({ onLogout }) {
  return (
    <header className="fixed top-0 md:left-64 left-0 right-0 h-16 bg-white shadow z-50 flex items-center px-6">
      <div className="flex-1 flex items-center">
        <FaGraduationCap className="text-blue-600 mr-3" aria-hidden="true" />
        <h1 className="text-lg font-semibold">Manajemen Keuangan & SPP Handayani</h1>
      </div>
      <div className="flex items-center space-x-4">
        <div className="text-sm text-gray-700">
          <span className="font-medium">Admin</span>
        </div>
        <div className="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center">
          <span className="text-sm font-medium text-white">A</span>
        </div>
        <button
          onClick={onLogout}
          className="text-sm text-blue-600 hover:text-blue-800"
          aria-label="Logout"
          title="Logout"
        >
          <FaSignOutAlt className="inline-block" />
        </button>
      </div>
    </header>
  );
}
