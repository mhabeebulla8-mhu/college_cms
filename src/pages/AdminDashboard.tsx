import React, { useState, useEffect } from 'react';
import { collection, query, onSnapshot, orderBy, updateDoc, doc, deleteDoc, where } from 'firebase/firestore';
import { db, handleFirestoreError, OperationType } from '../lib/firebase';
import { Complaint, UserProfile, COMPLAINT_CATEGORIES, ComplaintStatus } from '../types';
import { Clock, CheckCircle2, AlertCircle, FileText, ExternalLink, Trash2, Filter, Search, ChevronDown } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

const statusColors: Record<string, string> = {
  'Pending': 'bg-amber-100 text-amber-700 border-amber-200',
  'In Progress': 'bg-blue-100 text-blue-700 border-blue-200',
  'Resolved': 'bg-green-100 text-green-700 border-green-200'
};

export default function AdminDashboard({ profile }: { profile: UserProfile }) {
  const [complaints, setComplaints] = useState<Complaint[]>([]);
  const [loading, setLoading] = useState(true);
  const [filterCategory, setFilterCategory] = useState('All');
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    const q = query(collection(db, 'complaints'), orderBy('createdAt', 'desc'));

    const unsubscribe = onSnapshot(q, (snapshot) => {
      const docs = snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() } as Complaint));
      setComplaints(docs);
      setLoading(false);
    }, (error) => {
      handleFirestoreError(error, OperationType.LIST, 'complaints');
      setLoading(false);
    });

    return () => unsubscribe();
  }, []);

  const handleStatusUpdate = async (complaintId: string, newStatus: ComplaintStatus) => {
    try {
      await updateDoc(doc(db, 'complaints', complaintId), { status: newStatus });
    } catch (error) {
      handleFirestoreError(error, OperationType.UPDATE, `complaints/${complaintId}`);
    }
  };

  const handleDelete = async (complaintId: string) => {
    if (!window.confirm("Are you sure you want to delete this complaint?")) return;
    try {
      await deleteDoc(doc(db, 'complaints', complaintId));
    } catch (error) {
      handleFirestoreError(error, OperationType.DELETE, `complaints/${complaintId}`);
    }
  };

  const filteredComplaints = complaints.filter(c => {
    const matchesCategory = filterCategory === 'All' || c.category === filterCategory;
    const matchesSearch = c.studentName.toLowerCase().includes(searchQuery.toLowerCase()) || 
                          c.description.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  return (
    <div className="space-y-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <h2 className="text-3xl font-bold text-slate-900">Admin Panel</h2>
          <p className="text-slate-500 mt-1">Manage and resolve student grievances across all committees.</p>
        </div>
        
        <div className="flex flex-wrap gap-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
            <input 
              type="text"
              placeholder="Search complaints..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all w-64"
            />
          </div>
          
          <div className="relative">
            <Filter className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
            <select
              value={filterCategory}
              onChange={(e) => setFilterCategory(e.target.value)}
              className="pl-10 pr-8 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all appearance-none cursor-pointer"
            >
              <option value="All">All Categories</option>
              {COMPLAINT_CATEGORIES.map(cat => (
                <option key={cat} value={cat}>{cat}</option>
              ))}
            </select>
            <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4 pointer-events-none" />
          </div>
        </div>
      </div>

      {loading ? (
        <div className="flex justify-center py-20">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
      ) : filteredComplaints.length === 0 ? (
        <div className="bg-white border border-dashed border-slate-300 rounded-3xl py-20 text-center space-y-4">
          <h3 className="text-xl font-bold text-slate-900">No complaints found</h3>
          <p className="text-slate-500">Try adjusting your filters or search query.</p>
        </div>
      ) : (
        <div className="space-y-6">
          <AnimatePresence mode="popLayout">
            {filteredComplaints.map((complaint) => (
              <motion.div
                key={complaint.id}
                layout
                initial={{ opacity: 0, scale: 0.98 }}
                animate={{ opacity: 1, scale: 1 }}
                exit={{ opacity: 0, scale: 0.95 }}
                className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-all"
              >
                <div className="p-6 md:p-8">
                  <div className="flex flex-wrap items-start justify-between gap-4 mb-6">
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <h3 className="font-bold text-slate-900">{complaint.studentName}</h3>
                        <span className="text-xs text-slate-400">•</span>
                        <span className="text-xs font-bold text-blue-600 uppercase tracking-wider">{complaint.category}</span>
                      </div>
                      <p className="text-xs text-slate-400">
                        Submitted on {new Date(complaint.createdAt).toLocaleString()}
                      </p>
                    </div>
                    
                    <div className="flex items-center gap-3">
                      <div className="relative">
                        <select
                          value={complaint.status}
                          onChange={(e) => handleStatusUpdate(complaint.id, e.target.value as ComplaintStatus)}
                          className={`pl-4 pr-8 py-1.5 rounded-full text-xs font-bold border appearance-none cursor-pointer transition-all ${statusColors[complaint.status]}`}
                        >
                          <option value="Pending">Pending</option>
                          <option value="In Progress">In Progress</option>
                          <option value="Resolved">Resolved</option>
                        </select>
                        <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-3 h-3 pointer-events-none" />
                      </div>
                      
                      <button 
                        onClick={() => handleDelete(complaint.id)}
                        className="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                        title="Delete Complaint"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </div>

                  <div className="space-y-4">
                    <p className="text-slate-700 leading-relaxed whitespace-pre-wrap">{complaint.description}</p>
                    
                    {complaint.filePath && (
                      <div className="pt-4 flex items-center gap-3">
                        <a 
                          href={complaint.filePath} 
                          target="_blank" 
                          rel="noopener noreferrer"
                          className="flex items-center gap-2 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-600 hover:bg-slate-100 transition-colors"
                        >
                          <FileText className="w-4 h-4" />
                          <span className="font-medium">View Attachment</span>
                          <ExternalLink className="w-4 h-4" />
                        </a>
                      </div>
                    )}
                  </div>
                </div>
              </motion.div>
            ))}
          </AnimatePresence>
        </div>
      )}
    </div>
  );
}
