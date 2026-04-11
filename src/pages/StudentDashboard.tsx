import React, { useState, useEffect } from 'react';
import { collection, query, where, onSnapshot, orderBy } from 'firebase/firestore';
import { db, handleFirestoreError, OperationType } from '../lib/firebase';
import { Complaint, UserProfile } from '../types';
import { Clock, CheckCircle2, AlertCircle, FileText, ExternalLink, Inbox } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

const statusColors: Record<string, string> = {
  'Pending': 'bg-amber-100 text-amber-700 border-amber-200',
  'In Progress': 'bg-blue-100 text-blue-700 border-blue-200',
  'Resolved': 'bg-green-100 text-green-700 border-green-200'
};

const statusIcons: Record<string, React.ReactNode> = {
  'Pending': <Clock className="w-4 h-4" />,
  'In Progress': <AlertCircle className="w-4 h-4" />,
  'Resolved': <CheckCircle2 className="w-4 h-4" />
};

export default function StudentDashboard({ profile }: { profile: UserProfile }) {
  const [complaints, setComplaints] = useState<Complaint[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const q = query(
      collection(db, 'complaints'),
      where('userId', '==', profile.uid),
      orderBy('createdAt', 'desc')
    );

    const unsubscribe = onSnapshot(q, (snapshot) => {
      const docs = snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() } as Complaint));
      setComplaints(docs);
      setLoading(false);
    }, (error) => {
      handleFirestoreError(error, OperationType.LIST, 'complaints');
      setLoading(false);
    });

    return () => unsubscribe();
  }, [profile.uid]);

  return (
    <div className="space-y-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 className="text-3xl font-bold text-slate-900">My Complaints</h2>
          <p className="text-slate-500 mt-1">Track the status of your submitted grievances.</p>
        </div>
        <div className="bg-white px-6 py-3 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
          <div className="bg-blue-100 p-2 rounded-lg">
            <Inbox className="text-blue-600 w-5 h-5" />
          </div>
          <div>
            <p className="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Submitted</p>
            <p className="text-xl font-bold text-slate-900">{complaints.length}</p>
          </div>
        </div>
      </div>

      {loading ? (
        <div className="flex justify-center py-20">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
      ) : complaints.length === 0 ? (
        <div className="bg-white border border-dashed border-slate-300 rounded-3xl py-20 text-center space-y-4">
          <div className="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto">
            <Inbox className="text-slate-300 w-10 h-10" />
          </div>
          <h3 className="text-xl font-bold text-slate-900">No complaints yet</h3>
          <p className="text-slate-500 max-w-xs mx-auto">You haven't submitted any complaints. Click the button below to lodge your first grievance.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-6">
          <AnimatePresence mode="popLayout">
            {complaints.map((complaint) => (
              <motion.div
                key={complaint.id}
                layout
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95 }}
                className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-all"
              >
                <div className="p-6 md:p-8">
                  <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <div className="flex items-center gap-3">
                      <span className="px-4 py-1.5 bg-slate-100 text-slate-600 rounded-full text-xs font-bold uppercase tracking-wider">
                        {complaint.category}
                      </span>
                      <span className="text-xs text-slate-400 font-medium">
                        {new Date(complaint.createdAt).toLocaleDateString(undefined, { 
                          year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' 
                        })}
                      </span>
                    </div>
                    <div className={`flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold border ${statusColors[complaint.status]}`}>
                      {statusIcons[complaint.status]}
                      {complaint.status}
                    </div>
                  </div>

                  <div className="space-y-4">
                    <p className="text-slate-700 leading-relaxed whitespace-pre-wrap">{complaint.description}</p>
                    
                    {complaint.filePath && (
                      <div className="pt-4 flex items-center gap-3">
                        <div className="flex items-center gap-2 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-600 hover:bg-slate-100 transition-colors">
                          <FileText className="w-4 h-4" />
                          <span className="font-medium">Attachment</span>
                          <a 
                            href={complaint.filePath} 
                            target="_blank" 
                            rel="noopener noreferrer"
                            className="ml-2 text-blue-600 hover:text-blue-700"
                          >
                            <ExternalLink className="w-4 h-4" />
                          </a>
                        </div>
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
