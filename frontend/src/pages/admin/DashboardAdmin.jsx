import React from "react";
import { FaUsers, FaDollarSign, FaCheckCircle, FaClock, FaCreditCard, FaArrowUp } from "react-icons/fa";

export default function DashboardAdmin({ stats = null, recentPayments = null }) {
  // fallback sample data if props not provided
  const s = stats || { totalStudents: 145, totalIncome: "Rp 25.450.000", paid: 125, pending: 20 };
  const payments =
    recentPayments ||
    [
      { name: "Ahmad Fauzan", date: "Januari 2024", method: "Tunai", amount: "Rp 350.000", status: "Lunas" },
      { name: "Siti Nurhaliza", date: "Januari 2024", method: "Transfer", amount: "Rp 350.000", status: "Lunas" },
      { name: "Bambang Prasetyo", date: "Januari 2024", method: "Tunai", amount: "Rp 350.000", status: "Lunas" },
    ];

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="mt-1 text-sm text-gray-600">Selamat datang, Admin — ringkasan sistem SPP.</p>
      </div>

      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0 bg-blue-500 rounded-md p-3">
              <FaUsers className="h-6 w-6 text-white" />
            </div>
            <div className="ml-5 w-0 flex-1">
              <dl>
                <dt className="text-sm font-medium text-gray-500 truncate">Total Siswa</dt>
                <dd className="text-lg font-medium text-gray-900">{s.totalStudents}</dd>
              </dl>
            </div>
          </div>
          <div className="mt-4">
            <div className="flex items-center text-sm">
              <FaArrowUp className="h-4 w-4 text-green-500 mr-1" />
              <span className="font-medium text-green-600">+5</span>
              <span className="text-gray-500 ml-1">dari bulan lalu</span>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0 bg-green-500 rounded-md p-3">
              <FaDollarSign className="h-6 w-6 text-white" />
            </div>
            <div className="ml-5 w-0 flex-1">
              <dl>
                <dt className="text-sm font-medium text-gray-500 truncate">Total Pendapatan</dt>
                <dd className="text-lg font-medium text-gray-900">{s.totalIncome}</dd>
              </dl>
            </div>
          </div>
          <div className="mt-4">
            <div className="flex items-center text-sm">
              <FaArrowUp className="h-4 w-4 text-green-500 mr-1" />
              <span className="font-medium text-green-600">+12.5%</span>
              <span className="text-gray-500 ml-1">dari bulan lalu</span>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0 bg-green-600 rounded-md p-3">
              <FaCheckCircle className="h-6 w-6 text-white" />
            </div>
            <div className="ml-5 w-0 flex-1">
              <dl>
                <dt className="text-sm font-medium text-gray-500 truncate">Pembayaran Lunas</dt>
                <dd className="text-lg font-medium text-gray-900">{s.paid}</dd>
              </dl>
            </div>
          </div>
          <div className="mt-4">
            <div className="flex items-center text-sm">
              <span className="font-medium text-gray-600">{((s.paid / s.totalStudents) * 100).toFixed(1)}%</span>
              <span className="text-gray-500 ml-1">dari total</span>
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
                <dt className="text-sm font-medium text-gray-500 truncate">Menunggu Pembayaran</dt>
                <dd className="text-lg font-medium text-gray-900">{s.pending}</dd>
              </dl>
            </div>
          </div>
          <div className="mt-4">
            <div className="flex items-center text-sm">
              <span className="font-medium text-gray-600">{((s.pending / s.totalStudents) * 100).toFixed(1)}%</span>
              <span className="text-gray-500 ml-1">dari total</span>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Pembayaran Terbaru</h3>
        <div className="space-y-3">
          {payments.map((p, i) => (
            <div key={i} className="flex items-center justify-between py-2">
              <div className="flex items-center space-x-3">
                <div className="flex-shrink-0">
                  <div className="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                    <FaCreditCard className="h-4 w-4 text-green-600" />
                  </div>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-900">{p.name}</p>
                  <p className="text-xs text-gray-500">{p.date} • {p.method}</p>
                </div>
              </div>
              <div className="text-right">
                <p className="text-sm font-medium text-gray-900">{p.amount}</p>
                <p className="text-xs text-green-600">{p.status}</p>
              </div>
            </div>
          ))}
        </div>
        <div className="mt-4 pt-4 border-t border-gray-200">
          <button className="text-sm text-blue-600 hover:text-blue-700 font-medium">Lihat semua pembayaran →</button>
        </div>
      </div>
    </div>
  );
}