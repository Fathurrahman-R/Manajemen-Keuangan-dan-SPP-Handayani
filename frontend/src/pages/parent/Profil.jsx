import React from "react";

export default function Profil({ student = null, guardian = null }) {
  const s = student || {
    name: "Ahmad Fauzan",
    nis: "2023001",
    kelas: "X IPA 1",
    phone: "081234567890",
    email: "ahmad.fauzan@email.com",
    address: "Jl. Merdeka No. 123, Kota Contoh",
  };

  const g = guardian || {
    name: "Budi Santoso",
    relation: "Ayah Kandung",
    phone: "081234567890",
    email: "budi.santoso@email.com",
    spp: "Rp 350.000",
    status: "Aktif",
  };

  return (
    <div className="p-6">
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Profil Siswa</h1>
        <p className="mt-1 text-sm text-gray-600">Informasi lengkap siswa dan wali</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Data Siswa</h3>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
              <p className="text-sm text-gray-900 font-medium">{s.name}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Nomor Induk Siswa (NIS)</label>
              <p className="text-sm text-gray-900 font-medium">{s.nis}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
              <p className="text-sm text-gray-900 font-medium">{s.kelas}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
              <p className="text-sm text-gray-900 font-medium">{s.phone}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <p className="text-sm text-gray-900 font-medium">{s.email}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
              <p className="text-sm text-gray-900">{s.address}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Data Wali Murid</h3>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Nama Wali</label>
              <p className="text-sm text-gray-900 font-medium">{g.name}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Hubungan</label>
              <p className="text-sm text-gray-900 font-medium">{g.relation}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Telepon Wali</label>
              <p className="text-sm text-gray-900 font-medium">{g.phone}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Email Wali</label>
              <p className="text-sm text-gray-900 font-medium">{g.email}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">SPP Bulanan</label>
              <p className="text-sm text-gray-900 font-medium">{g.spp}</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Status Siswa</label>
              <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">{g.status}</span>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Informasi Kontak Sekolah</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p className="text-sm text-gray-600 mb-1">Telepon Sekolah</p>
            <p className="text-sm font-medium text-gray-900">(021) 1234567</p>
          </div>
          <div>
            <p className="text-sm text-gray-600 mb-1">Email Sekolah</p>
            <p className="text-sm font-medium text-gray-900">info@smacontoh.sch.id</p>
          </div>
          <div className="md:col-span-2">
            <p className="text-sm text-gray-600 mb-1">Alamat Sekolah</p>
            <p className="text-sm font-medium text-gray-900">Jl. Pendidikan No. 123, Kota Contoh</p>
          </div>
        </div>
      </div>
    </div>
  );
}
