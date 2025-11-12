import React from "react";
import { FaDownload } from "react-icons/fa";

export default function Tagihan({ invoices = [] }) {
  const data = invoices.length
    ? invoices
    : [
        { month: "Januari 2024", amount: "Rp 350.000", due: "10 Jan 2024", status: "Lunas" },
        { month: "Februari 2024", amount: "Rp 350.000", due: "10 Feb 2024", status: "Lunas" },
        { month: "Maret 2024", amount: "Rp 350.000", due: "10 Mar 2024", status: "Menunggu" },
      ];

  return (
    <div className="p-6">
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Tagihan SPP</h1>
        <p className="mt-1 text-sm text-gray-600">Daftar tagihan SPP untuk siswa</p>
      </div>

      <div className="flex flex-col sm:flex-row gap-4 mb-6">
        <div className="flex-1">
          <select className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option>Semua Status</option>
            <option>Lunas</option>
            <option>Belum Lunas</option>
            <option>Jatuh Tempo</option>
          </select>
        </div>
        <div className="flex-1">
          <select className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option>Semua Tahun</option>
            <option>2024</option>
            <option>2023</option>
          </select>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bulan</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jatuh Tempo</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {data.map((row) => (
                <tr key={row.month} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{row.month}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{row.amount}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{row.due}</td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                      row.status === "Lunas" ? "bg-green-100 text-green-800" : row.status === "Menunggu" ? "bg-yellow-100 text-yellow-800" : "bg-gray-100 text-gray-800"
                    }`}>{row.status}</span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    {row.status === "Lunas" ? (
                      <button className="text-blue-600 hover:text-blue-800"><FaDownload /></button>
                    ) : (
                      <button className="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded-full">Bayar</button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
