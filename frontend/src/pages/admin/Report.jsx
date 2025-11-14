import React from "react";
import { FaDollarSign, FaUsers, FaChartBar } from "react-icons/fa";

export default function Report() {
  const monthly = [
    { month: "Januari", totalStudents: 145, paid: 125, unpaid: 20, revenue: "Rp 3.850.000" , rate: "86.2%"},
    { month: "Februari", totalStudents: 148, paid: 130, unpaid: 18, revenue: "Rp 3.920.000" , rate: "87.8%"},
  ];

  return (
    <div className="p-6">
      <div className="mb-6">
        <h2 className="text-2xl font-semibold">Laporan Keuangan</h2>
        <p className="text-sm text-gray-600">Analisis dan ringkasan pendapatan SPP per bulan</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center">
          <div className="p-3 rounded-md bg-blue-500 text-white mr-4"><FaDollarSign /></div>
          <div>
            <p className="text-sm text-gray-500">Total Pendapatan</p>
            <p className="text-xl font-bold">Rp 25.450.000</p>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center">
          <div className="p-3 rounded-md bg-green-500 text-white mr-4"><FaUsers /></div>
          <div>
            <p className="text-sm text-gray-500">Total Siswa</p>
            <p className="text-xl font-bold">145</p>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center">
          <div className="p-3 rounded-md bg-yellow-500 text-white mr-4"><FaChartBar /></div>
          <div>
            <p className="text-sm text-gray-500">Rata-rata Bayar</p>
            <p className="text-xl font-bold">88.5%</p>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center">
          <div className="p-3 rounded-md bg-purple-500 text-white mr-4"><FaChartBar /></div>
          <div>
            <p className="text-sm text-gray-500">Rata-rata / Bulan</p>
            <p className="text-xl font-bold">Rp 4.240.000</p>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bulan</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Siswa</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sudah Bayar</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Belum Bayar</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tingkat Bayar</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Pendapatan</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {monthly.map((m) => (
                <tr key={m.month} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{m.month}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{m.totalStudents}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">{m.paid}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-yellow-600 font-medium">{m.unpaid}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{m.rate}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{m.revenue}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}