import React from "react";

export default function Footer() {
  return (
    <footer className="fixed bottom-0 md:left-64 left-0 right-0 h-12 bg-white border-t shadow-inner flex items-center px-6 z-40">
      <div className="w-full text-center text-sm text-gray-600">
        © {new Date().getFullYear()} Handayani — Semua hak dilindungi.
      </div>
    </footer>
  );
}