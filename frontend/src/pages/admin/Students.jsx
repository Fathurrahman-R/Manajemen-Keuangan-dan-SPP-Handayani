import React, { useState, useEffect } from "react";
import { FaUsers, FaPlus, FaEdit, FaTrash, FaTimes } from "react-icons/fa";

export default function Students() {
  const [activeTab, setActiveTab] = useState("TK");

  // Sample data structure updated to match database schema
  const [tkData, setTkData] = useState([
    {
      id: 1,
      nis: "TK001",
      nisn: "0001234567",
      nama_lengkap: "Andi Pratama",
      jenis_kelamin: "Laki",
      tmpt_lahir: "Jakarta",
      tgl_lahir: "2018-05-15",
      agama: "Islam",
      alamat: "Jl. Merdeka No. 1",
      jenjang: "TK",
      id_kelas: 1,
      status: "Aktif",
    },
  ]);
  const [miData, setMiData] = useState([]);
  const [kbData, setKbData] = useState([]);

  // form state - expanded to match database schema
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState({
    nis: "",
    nisn: "",
    nama_lengkap: "",
    jenis_kelamin: "Laki",
    tmpt_lahir: "",
    tgl_lahir: "",
    agama: "Islam",
    alamat: "",
    id_ayah: "",
    id_ibu: "",
    id_wali: "",
    jenjang: "TK",
    id_kelas: "",
    id_kategori: "",
    asal_sekolah: "",
    kls_diterima: "",
    thn_diterima: "",
    status: "Aktif",
    ket: "",
  });

  const tabs = [
    { id: "TK", label: "TK", icon: <FaUsers /> },
    { id: "MI", label: "MI", icon: <FaUsers /> },
    { id: "KB", label: "KB", icon: <FaUsers /> },
  ];

  // helper to get/set data for current tab
  const getData = () => {
    if (activeTab === "TK") return tkData;
    if (activeTab === "MI") return miData;
    return kbData;
  };
  const setData = (arr) => {
    if (activeTab === "TK") setTkData(arr);
    else if (activeTab === "MI") setMiData(arr);
    else setKbData(arr);
  };

  // when switching tab reset form and set jenjang
  useEffect(() => {
    setShowForm(false);
    setEditing(null);
    setForm((prev) => ({
      nis: "",
      nisn: "",
      nama_lengkap: "",
      jenis_kelamin: "Laki",
      tmpt_lahir: "",
      tgl_lahir: "",
      agama: "Islam",
      alamat: "",
      id_ayah: "",
      id_ibu: "",
      id_wali: "",
      jenjang: activeTab,
      id_kelas: "",
      id_kategori: "",
      asal_sekolah: "",
      kls_diterima: "",
      thn_diterima: "",
      status: "Aktif",
      ket: "",
    }));
  }, [activeTab]);

  const handleAddClick = () => {
    setEditing(null);
    setForm({
      nis: "",
      nisn: "",
      nama_lengkap: "",
      jenis_kelamin: "Laki",
      tmpt_lahir: "",
      tgl_lahir: "",
      agama: "Islam",
      alamat: "",
      id_ayah: "",
      id_ibu: "",
      id_wali: "",
      jenjang: activeTab,
      id_kelas: "",
      id_kategori: "",
      asal_sekolah: "",
      kls_diterima: "",
      thn_diterima: "",
      status: "Aktif",
      ket: "",
    });
    setShowForm(true);
  };

  const handleEdit = (item) => {
    setEditing(item.id);
    setForm({ ...item });
    setShowForm(true);
  };

  const handleDelete = (id) => {
    const newArr = getData().filter((r) => r.id !== id);
    setData(newArr);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const list = getData();
    if (editing) {
      // update
      const updated = list.map((r) => (r.id === editing ? { ...r, ...form } : r));
      setData(updated);
    } else {
      // add
      const nextId = list.length ? Math.max(...list.map((r) => r.id)) + 1 : 1;
      setData([...list, { id: nextId, ...form }]);
    }
    setShowForm(false);
    setEditing(null);
    setForm({
      nis: "",
      nisn: "",
      nama_lengkap: "",
      jenis_kelamin: "Laki",
      tmpt_lahir: "",
      tgl_lahir: "",
      agama: "Islam",
      alamat: "",
      id_ayah: "",
      id_ibu: "",
      id_wali: "",
      jenjang: activeTab,
      id_kelas: "",
      id_kategori: "",
      asal_sekolah: "",
      kls_diterima: "",
      thn_diterima: "",
      status: "Aktif",
      ket: "",
    });
  };

  const currentData = getData();

  return (
    <div className="p-6 space-y-6">
      {/* Page title */}
      <div>
        <h2 className="text-2xl font-bold text-gray-800">Data Siswa</h2>
        <p className="text-sm text-gray-600 mt-1">
          Kelola data siswa berdasarkan jenjang pendidikan
        </p>
      </div>

      {/* Tabs */}
      <div className="bg-white rounded-lg shadow-sm border">
        <div className="flex border-b">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`flex-1 flex items-center justify-center gap-2 px-6 py-4 transition-all font-medium ${
                activeTab === tab.id
                  ? "text-blue-600 border-b-2 border-blue-600 bg-blue-50"
                  : "text-gray-600 hover:text-gray-900 hover:bg-gray-50"
              }`}
            >
              <span className="text-lg">{tab.icon}</span>
              <span>{tab.label}</span>
            </button>
          ))}
        </div>

        {/* Table header with title and button */}
        <div className="flex items-center justify-between px-6 py-4 bg-gray-50 border-b">
          <div>
            <h3 className="font-semibold text-gray-800">
              Data Siswa {activeTab}
            </h3>
            <p className="text-sm text-gray-600 mt-0.5">
              {currentData.length} siswa terdaftar
            </p>
          </div>
          <button
            onClick={() => {
              if (showForm) {
                setShowForm(false);
                setEditing(null);
              } else {
                handleAddClick();
              }
            }}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors shadow-sm ${
              showForm
                ? "bg-red-600 hover:bg-red-700 text-white"
                : "bg-green-600 hover:bg-green-700 text-white"
            }`}
          >
            {showForm ? (
              <>
                <FaTimes className="text-sm" />
                <span>Batal</span>
              </>
            ) : (
              <>
                <FaPlus className="text-sm" />
                <span>Tambah Data</span>
              </>
            )}
          </button>
        </div>

        {/* Form (add / edit) - expanded for all fields */}
        {showForm && (
          <div className="p-6 border-b bg-gray-50">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">
              {editing ? "Edit Data Siswa" : "Tambah Data Siswa"}
            </h3>
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Data Identitas Siswa */}
              <div>
                <h4 className="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">
                  Data Identitas Siswa
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      NIS <span className="text-red-500">*</span>
                    </label>
                    <input
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="Nomor Induk Siswa"
                      value={form.nis}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, nis: e.target.value }))
                      }
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      NISN <span className="text-red-500">*</span>
                    </label>
                    <input
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="Nomor Induk Siswa Nasional"
                      value={form.nisn}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, nisn: e.target.value }))
                      }
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Nama Lengkap <span className="text-red-500">*</span>
                    </label>
                    <input
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="Nama lengkap siswa"
                      value={form.nama_lengkap}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, nama_lengkap: e.target.value }))
                      }
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Jenis Kelamin <span className="text-red-500">*</span>
                    </label>
                    <select
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      value={form.jenis_kelamin}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, jenis_kelamin: e.target.value }))
                      }
                      required
                    >
                      <option value="Laki">Laki-laki</option>
                      <option value="Perempuan">Perempuan</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Tempat Lahir <span className="text-red-500">*</span>
                    </label>
                    <input
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="Kota/Kabupaten"
                      value={form.tmpt_lahir}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, tmpt_lahir: e.target.value }))
                      }
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Tanggal Lahir <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="date"
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      value={form.tgl_lahir}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, tgl_lahir: e.target.value }))
                      }
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Agama <span className="text-red-500">*</span>
                    </label>
                    <select
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      value={form.agama}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, agama: e.target.value }))
                      }
                      required
                    >
                      <option value="Islam">Islam</option>
                      <option value="Kristen">Kristen</option>
                      <option value="Katolik">Katolik</option>
                      <option value="Hindu">Hindu</option>
                      <option value="Buddha">Buddha</option>
                      <option value="Konghucu">Konghucu</option>
                    </select>
                  </div>
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Alamat <span className="text-red-500">*</span>
                    </label>
                    <textarea
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="Alamat lengkap"
                      rows="2"
                      value={form.alamat}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, alamat: e.target.value }))
                      }
                      required
                    />
                  </div>
                </div>
              </div>

              {/* Data Orang Tua/Wali */}
              <div>
                <h4 className="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">
                  Data Orang Tua/Wali
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  {/* ID Ayah and ID Ibu only shown for MI */}
                  {activeTab === "MI" && (
                    <>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          ID Ayah
                        </label>
                        <input
                          type="number"
                          className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                          placeholder="ID Ayah (opsional)"
                          value={form.id_ayah}
                          onChange={(e) =>
                            setForm((s) => ({ ...s, id_ayah: e.target.value }))
                          }
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          ID Ibu
                        </label>
                        <input
                          type="number"
                          className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                          placeholder="ID Ibu (opsional)"
                          value={form.id_ibu}
                          onChange={(e) =>
                            setForm((s) => ({ ...s, id_ibu: e.target.value }))
                          }
                        />
                      </div>
                    </>
                  )}
                  
                  {/* ID Wali shown for all jenjang */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      ID Wali
                    </label>
                    <input
                      type="number"
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="ID Wali (opsional)"
                      value={form.id_wali}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, id_wali: e.target.value }))
                      }
                    />
                  </div>
                </div>
              </div>

              {/* Data Sekolah */}
              <div>
                <h4 className="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">
                  Data Sekolah
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Jenjang <span className="text-red-500">*</span>
                    </label>
                    <select
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed"
                      value={form.jenjang}
                      disabled
                    >
                      <option value="TK">TK</option>
                      <option value="MI">MI</option>
                      <option value="KB">KB</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      ID Kelas <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="number"
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="ID Kelas"
                      value={form.id_kelas}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, id_kelas: e.target.value }))
                      }
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      ID Kategori <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="number"
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="ID Kategori"
                      value={form.id_kategori}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, id_kategori: e.target.value }))
                      }
                      required
                    />
                  </div>

                  {/* Fields below only shown for MI tab */}
                  {activeTab === "MI" && (
                    <>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          Asal Sekolah
                        </label>
                        <input
                          className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                          placeholder="Nama sekolah asal"
                          value={form.asal_sekolah}
                          onChange={(e) =>
                            setForm((s) => ({ ...s, asal_sekolah: e.target.value }))
                          }
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          Kelas Diterima
                        </label>
                        <input
                          className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                          placeholder="Kelas saat diterima"
                          value={form.kls_diterima}
                          onChange={(e) =>
                            setForm((s) => ({ ...s, kls_diterima: e.target.value }))
                          }
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          Tahun Diterima
                        </label>
                        <input
                          type="number"
                          min="2000"
                          max="2100"
                          className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                          placeholder="YYYY"
                          value={form.thn_diterima}
                          onChange={(e) =>
                            setForm((s) => ({ ...s, thn_diterima: e.target.value }))
                          }
                        />
                      </div>
                    </>
                  )}

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Status <span className="text-red-500">*</span>
                    </label>
                    <select
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      value={form.status}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, status: e.target.value }))
                      }
                      required
                    >
                      <option value="Aktif">Aktif</option>
                      <option value="Lulus">Lulus</option>
                      <option value="Pindah">Pindah</option>
                      <option value="Keluar">Keluar</option>
                    </select>
                  </div>
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Keterangan
                    </label>
                    <textarea
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="Keterangan tambahan (opsional)"
                      rows="2"
                      value={form.ket}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, ket: e.target.value }))
                      }
                    />
                  </div>
                </div>
              </div>

              <div className="flex gap-3 pt-2 border-t">
                <button
                  type="submit"
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors shadow-sm"
                >
                  {editing ? "Simpan Perubahan" : "Simpan Data"}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowForm(false);
                    setEditing(null);
                  }}
                  className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors"
                >
                  Batal
                </button>
              </div>
            </form>
          </div>
        )}

        {/* Table - updated columns */}
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-gray-100 border-b">
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  No
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  NIS
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  NISN
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  Nama Lengkap
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  JK
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  Kelas
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {currentData.length === 0 && (
                <tr>
                  <td colSpan="8" className="px-6 py-12 text-center">
                    <div className="text-gray-400">
                      <FaUsers className="mx-auto text-4xl mb-2" />
                      <p className="text-sm">Belum ada data siswa</p>
                    </div>
                  </td>
                </tr>
              )}
              {currentData.map((student, index) => (
                <tr key={student.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4 text-sm text-gray-700">{index + 1}</td>
                  <td className="px-6 py-4 text-sm text-gray-700">{student.nis}</td>
                  <td className="px-6 py-4 text-sm text-gray-700">{student.nisn}</td>
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">
                    {student.nama_lengkap}
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-700">
                    {student.jenis_kelamin}
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-700">{student.id_kelas}</td>
                  <td className="px-6 py-4 text-sm">
                    <span
                      className={`inline-flex px-2.5 py-1 rounded-full text-xs font-semibold ${
                        student.status === "Aktif"
                          ? "bg-green-100 text-green-800"
                          : student.status === "Lulus"
                          ? "bg-blue-100 text-blue-800"
                          : "bg-red-100 text-red-800"
                      }`}
                    >
                      {student.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-center">
                    <div className="flex items-center justify-center gap-2">
                      <button
                        onClick={() => handleEdit(student)}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-xs font-medium transition-colors"
                        title="Edit"
                      >
                        <FaEdit />
                        <span>Edit</span>
                      </button>
                      <button
                        onClick={() => handleDelete(student.id)}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-medium transition-colors"
                        title="Hapus"
                      >
                        <FaTrash />
                        <span>Hapus</span>
                      </button>
                    </div>
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