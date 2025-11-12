import React, { useState, useEffect } from "react";
import { FaUsers, FaPlus, FaEdit, FaTrash, FaTimes } from "react-icons/fa";

export default function Wali() {
  const [activeTab, setActiveTab] = useState("TK");

  // Sample data structure updated to match database schema
  const [tkData, setTkData] = useState([
    {
      id: 1,
      nama_wali: "Budi Santoso",
      jenis_kelamin: "Laki",
      agama: "Islam",
      pendidikan_terakhir: "S1",
      pekerjaan: "Wiraswasta",
      alamat: "Jl. Melati No. 5",
      no_hp: "081234567890",
      ket: "",
    },
  ]);
  const [miData, setMiData] = useState([]);
  const [kbData, setKbData] = useState([]);

  // form state
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState({
    nama_wali: "",
    jenis_kelamin: "Laki",
    agama: "Islam",
    pendidikan_terakhir: "",
    pekerjaan: "",
    alamat: "",
    no_hp: "",
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

  // when switching tab reset form state
  useEffect(() => {
    setShowForm(false);
    setEditing(null);
    setForm({
      nama_wali: "",
      jenis_kelamin: "Laki",
      agama: "Islam",
      pendidikan_terakhir: "",
      pekerjaan: "",
      alamat: "",
      no_hp: "",
      ket: "",
    });
  }, [activeTab]);

  const handleAddClick = () => {
    setEditing(null);
    setForm({
      nama_wali: "",
      jenis_kelamin: "Laki",
      agama: "Islam",
      pendidikan_terakhir: "",
      pekerjaan: "",
      alamat: "",
      no_hp: "",
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
      nama_wali: "",
      jenis_kelamin: "Laki",
      agama: "Islam",
      pendidikan_terakhir: "",
      pekerjaan: "",
      alamat: "",
      no_hp: "",
      ket: "",
    });
  };

  const currentData = getData();

  return (
    <div className="p-6 space-y-6">
      {/* Page title */}
      <div>
        <h2 className="text-2xl font-bold text-gray-800">Data Wali</h2>
        <p className="text-sm text-gray-600 mt-1">
          Kelola data wali siswa berdasarkan jenjang pendidikan
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
            <h3 className="font-semibold text-gray-800">Data Wali {activeTab}</h3>
            <p className="text-sm text-gray-600 mt-0.5">
              {currentData.length} wali terdaftar
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

        {/* Form (add / edit) */}
        {showForm && (
          <div className="p-6 border-b bg-gray-50">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">
              {editing ? "Edit Data Wali" : "Tambah Data Wali"}
            </h3>
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Data Wali */}
              <div>
                <h4 className="text-sm font-semibold text-gray-700 mb-3 border-b pb-2">
                  Data Wali
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Nama Wali <span className="text-red-500">*</span>
                    </label>
                    <input
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="Nama lengkap wali"
                      value={form.nama_wali}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, nama_wali: e.target.value }))
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
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Pendidikan Terakhir <span className="text-red-500">*</span>
                    </label>
                    <input
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="SD/SMP/SMA/S1/S2/S3"
                      value={form.pendidikan_terakhir}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, pendidikan_terakhir: e.target.value }))
                      }
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Pekerjaan <span className="text-red-500">*</span>
                    </label>
                    <input
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="Pekerjaan wali"
                      value={form.pekerjaan}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, pekerjaan: e.target.value }))
                      }
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      No. HP <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="tel"
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder="08xxxxxxxxxx"
                      value={form.no_hp}
                      onChange={(e) =>
                        setForm((s) => ({ ...s, no_hp: e.target.value }))
                      }
                      required
                    />
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
                  <div className="md:col-span-3">
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

        {/* Table */}
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-gray-100 border-b">
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  No
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  Nama Wali
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  JK
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  Pendidikan
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  Pekerjaan
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  No. HP
                </th>
                <th className="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {currentData.length === 0 && (
                <tr>
                  <td colSpan="7" className="px-6 py-12 text-center">
                    <div className="text-gray-400">
                      <FaUsers className="mx-auto text-4xl mb-2" />
                      <p className="text-sm">Belum ada data wali</p>
                    </div>
                  </td>
                </tr>
              )}
              {currentData.map((wali, index) => (
                <tr key={wali.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4 text-sm text-gray-700">{index + 1}</td>
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">
                    {wali.nama_wali}
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-700">
                    {wali.jenis_kelamin}
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-700">
                    {wali.pendidikan_terakhir}
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-700">{wali.pekerjaan}</td>
                  <td className="px-6 py-4 text-sm text-gray-700">{wali.no_hp}</td>
                  <td className="px-6 py-4 text-center">
                    <div className="flex items-center justify-center gap-2">
                      <button
                        onClick={() => handleEdit(wali)}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-xs font-medium transition-colors"
                        title="Edit"
                      >
                        <FaEdit />
                        <span>Edit</span>
                      </button>
                      <button
                        onClick={() => handleDelete(wali.id)}
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
