import React, { useState } from "react";
import {
  FaHome,
  FaUsers,
  FaCreditCard,
  FaChartBar,
  FaCog,
} from "react-icons/fa";

export default function Sidebar({ active, setActive }) {
  // control open state for groups (master, transaksi, laporan)
  // start all groups closed so submenu stays hidden until clicked
  const [openGroups, setOpenGroups] = useState({
    master: false,
    transaksi: false,
    laporan: false,
  });

  const navItems = [
    { id: "dashboard", label: "Dashboard", icon: <FaHome /> },

    {
      id: "master",
      label: "Data Master",
      icon: <FaUsers />,
      children: [
        { id: "students", label: "Siswa", icon: <FaUsers /> },
        { id: "wali", label: "Wali", icon: <FaUsers /> },
        { id: "kategori", label: "Kategori", icon: <FaChartBar /> },
        { id: "kelas", label: "Kelas", icon: <FaCog /> },
      ],
    },

    {
      id: "transaksi",
      label: "Transaksi",
      icon: <FaCreditCard />,
      children: [
        { id: "jenis_tagihan", label: "Jenis Tagihan", icon: <FaChartBar /> },
        { id: "tagihan", label: "Tagihan", icon: <FaCreditCard /> },
        { id: "pembayaran", label: "Pembayaran", icon: <FaCreditCard /> },
        { id: "pengeluaran", label: "Pengeluaran", icon: <FaChartBar /> },
      ],
    },

    {
      id: "laporan",
      label: "Laporan",
      icon: <FaChartBar />,
      children: [
        { id: "kas_harian", label: "Kas Harian", icon: <FaChartBar /> },
        { id: "rekap_bulanan", label: "Rekap Bulanan", icon: <FaChartBar /> },
      ],
    },

    { id: "settings", label: "Pengaturan", icon: <FaCog /> },
  ];

  const toggleGroup = (key) => {
    setOpenGroups((prev) => ({ ...prev, [key]: !prev[key] }));
  };

  // Sidebar fixed, always width w-64 and cannot be hidden
  return (
    <aside
      className="fixed top-0 left-0 bottom-0 w-64 bg-white shadow-lg overflow-auto z-40"
      aria-expanded="true"
    >
      {/* top area inside sidebar */}
      <div className="flex items-center justify-between h-16 px-4 border-b">
        <div className="flex items-center space-x-3">
          <div className="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
            H
          </div>
          <span className="font-semibold text-sm">Handayani</span>
        </div>
      </div>

      {/* nav area */}
      <nav className="mt-2 px-2">
        {navItems.map((item) => {
          if (item.children) {
            const childIds = item.children.map((c) => c.id);
            const isParentActive = childIds.includes(active) || active === item.id;
            const open = !!openGroups[item.id];

            return (
              <div key={item.id} className="mb-1">
                <button
                  onClick={() => toggleGroup(item.id)}
                  className={`group flex items-center w-full transition-colors px-2 py-2 text-sm font-medium rounded-md ${
                    isParentActive
                      ? "bg-blue-50 text-blue-700 border-r-2 border-blue-600"
                      : "text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                  }`}
                >
                  <span className="flex items-center justify-center mr-3 text-gray-400 group-hover:text-gray-500">
                    {item.icon}
                  </span>

                  <span className="truncate">{item.label}</span>
                </button>

                {/* submenu (hidden until group is opened) */}
                {open && (
                  <div className="mt-1 pl-8">
                    {item.children.map((child) => {
                      const isActive = active === child.id;
                      return (
                        <button
                          key={child.id}
                          onClick={() => setActive(child.id)}
                          className={`group flex items-center w-full transition-colors px-2 py-2 text-sm rounded-md mb-1 ${
                            isActive ? "bg-blue-50 text-blue-700" : "text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                          }`}
                        >
                          <span className="mr-3 text-gray-400">{child.icon}</span>
                          <span className="truncate">{child.label}</span>
                        </button>
                      );
                    })}
                  </div>
                )}
              </div>
            );
          }

          const isActive = active === item.id;
          return (
            <button
              key={item.id}
              onClick={() => setActive(item.id)}
              className={`group flex items-center w-full transition-colors px-2 py-2 text-sm font-medium rounded-md mb-1 ${
                isActive ? "bg-blue-50 text-blue-700 border-r-2 border-blue-600" : "text-gray-600 hover:text-gray-900 hover:bg-gray-50"
              }`}
            >
              <span className="flex items-center justify-center mr-3 text-gray-400 group-hover:text-gray-500" aria-hidden="true">
                {item.icon}
              </span>

              <span className="truncate">{item.label}</span>
            </button>
          );
        })}
      </nav>
    </aside>
  );
}
