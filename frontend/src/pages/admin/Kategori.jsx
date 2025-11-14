import React, { useState, useEffect } from "react";
import { FaFolder, FaPlus, FaEdit, FaTrash, FaTimes } from "react-icons/fa";

export default function Kategori() {
  const [activeTab, setActiveTab] = useState("TK");

  // Sample data structure
  const [tkData, setTkData] = useState([
    { id: 1, nama_kategori: "Kategori A" },
    { id: 2, nama_kategori: "Kategori B" },
  ]);
  const [miData, setMiData] = useState([]);
  const [kbData, setKbData] = useState([]);

  // form state
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState({
    nama_kategori: "",
  });

  const tabs = [
    { id: "TK", label: "TK", icon: <FaFolder /> },
    { id: "MI", label: "MI", icon: <FaFolder /> },
    { id: "KB", label: "KB", icon: <FaFolder /> },
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
    setForm({ nama_kategori: "" });
  }, [activeTab]);

  const handleAddClick = () => {
    setEditing(null);
    setForm({ nama_kategori: "" });
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
    setForm({ nama_kategori: "" });
  };

  const currentData = getData();

  return (
    <div className="p-6 space-y-6">
      {/* Page title */}
      <div>
        <h2 className="text-2xl font-bold text-gray-800">Data Kategori</h2>
        <p className="text-sm text-gray-600 mt-1">
          Kelola kategori siswa berdasarkan jenjang pendidikan
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
            <h3 className="font-semibold text-gray-800">Kategori {activeTab}</h3>
            <p className="text-sm text-gray-600 mt-0.5">
              {currentData.length} kategori terdaftar
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
              {editing ? "Edit Kategori" : "Tambah Kategori"}
            </h3>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Nama Kategori <span className="text-red-500">*</span>
                  </label>
                  <input
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                    placeholder="Masukkan nama kategori"
                    value={form.nama_kategori}
                    onChange={(e) =>
                      setForm((s) => ({ ...s, nama_kategori: e.target.value }))
                    }
                    required
                  />
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
                  Nama Kategori
                </th>
                <th className="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {currentData.length === 0 && (
                <tr>
                  <td colSpan="3" className="px-6 py-12 text-center">
                    <div className="text-gray-400">
                      <FaFolder className="mx-auto text-4xl mb-2" />
                      <p className="text-sm">Belum ada data kategori</p>
                    </div>
                  </td>
                </tr>
              )}
              {currentData.map((kategori, index) => (
                <tr key={kategori.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4 text-sm text-gray-700">{index + 1}</td>
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">
                    {kategori.nama_kategori}
                  </td>
                  <td className="px-6 py-4 text-center">
                    <div className="flex items-center justify-center gap-2">
                      <button
                        onClick={() => handleEdit(kategori)}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-xs font-medium transition-colors"
                        title="Edit"
                      >
                        <FaEdit />
                        <span>Edit</span>
                      </button>
                      <button
                        onClick={() => handleDelete(kategori.id)}
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
