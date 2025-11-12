import React from "react";
import { FaUsers, FaDollarSign, FaCheckCircle, FaClock, FaCreditCard, FaArrowUp } from "react-icons/fa";

export default function DashboardUser({ student = null, stats = null, recentPayments = null }) {
  const s = student || { name: "Ahmad Fauzan", nis: "2023001", kelas: "X IPA 1", wali: "Budi Santoso", waliPhone: "081234567890" };
  const st = stats || { totalBill: "Rp 4.200.000", paid: "Rp 2.100.000", pending: "Rp 2.100.000" };
  const payments =
    recentPayments ||
    [
      { name: "Pembayaran Januari 2024", date: "05 Jan 2024", method: "Tunai", amount: "Rp 350.000", status: "Lunas" },
      { name: "Pembayaran Februari 2024", date: "07 Feb 2024", method: "Transfer", amount: "Rp 350.000", status: "Lunas" },
      { name: "Pembayaran Maret 2024", date: "10 Mar 2024", method: "Transfer", amount: "Rp 350.000", status: "Menunggu" },
    ];

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="mt-1 text-sm text-gray-600">Ringkasan pembayaran untuk siswa {s.name}</p>
      </div>

      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0 bg-blue-500 rounded-md p-3">
              <FaUsers className="h-6 w-6 text-white" />
            </div>
            <div className="ml-5 w-0 flex-1">
              <dl>
                <dt className="text-sm font-medium text-gray-500 truncate">Nama Siswa</dt>
                <dd className="text-lg font-medium text-gray-900">{s.name}</dd>
              </dl>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0 bg-blue-500 rounded-md p-3">
              <FaDollarSign className="h-6 w-6 text-white" />
            </div>
            <div className="ml-5 w-0 flex-1">
              <dl>
                <dt className="text-sm font-medium text-gray-500 truncate">Total Tagihan</dt>
                <dd className="text-lg font-medium text-gray-900">{st.totalBill}</dd>
              </dl>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0 bg-green-500 rounded-md p-3">
              <FaCheckCircle className="h-6 w-6 text-white" />
            </div>
            <div className="ml-5 w-0 flex-1">
              <dl>
                <dt className="text-sm font-medium text-gray-500 truncate">Sudah Dibayar</dt>
                <dd className="text-lg font-medium text-gray-900">{st.paid}</dd>
              </dl>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0 bg-yellow-500 rounded-md p-3">
              <FaClock className="h-6 w-6 text-white" />
            </div>
            <div className="ml-5 w-0 flex-1">
              <dl>
                <dt className="text-sm font-medium text-gray-500 truncate">Menunggu Bayar</dt>
                <dd className="text-lg font-medium text-gray-900">{st.pending}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Aktivitas Terbaru</h3>
        <div className="space-y-3">
          {payments.map((p, i) => (
            <div key={i} className="flex items-center justify-between py-2">
              <div className="flex items-center space-x-3">
                <div className={`flex-shrink-0 ${p.status === "Lunas" ? "bg-green-100" : p.status === "Menunggu" ? "bg-yellow-100" : "bg-gray-100"} rounded-full p-2`}>
                  <FaCreditCard className="text-green-600" />
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-900">{p.name}</p>
                  <p className="text-xs text-gray-500">{p.date} • {p.method}</p>
                </div>
              </div>
              <div className="text-right">
                <p className="text-sm font-medium text-gray-900">{p.amount}</p>
                <p className={`text-xs ${p.status === "Lunas" ? "text-green-600" : "text-yellow-600"}`}>{p.status}</p>
              </div>
            </div>
          ))}
        </div>
        <div className="mt-4 pt-4 border-t border-gray-200">
          <button className="text-sm text-blue-600 hover:text-blue-700 font-medium">Lihat semua tagihan →</button>
        </div>
      </div>
    </div>
  );
}
