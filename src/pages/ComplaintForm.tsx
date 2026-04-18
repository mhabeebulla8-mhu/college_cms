import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { addDoc, collection } from 'firebase/firestore';
import { db, handleFirestoreError, OperationType } from '../lib/firebase';
import { COMPLAINT_CATEGORIES, UserProfile } from '../types';
import { Send, Upload, AlertCircle, CheckCircle2, FileText } from 'lucide-react';
import { GoogleGenAI } from "@google/genai";
import { useSearchParams } from 'react-router-dom';

const ai = new GoogleGenAI({ apiKey: process.env.GEMINI_API_KEY });

export default function ComplaintForm({ profile }: { profile: UserProfile }) {
  const [searchParams] = useSearchParams();
  const urlCategory = searchParams.get('category') || '';
  
  const [category, setCategory] = useState(urlCategory);
  
  // Update state whenever the URL param changes
  React.useEffect(() => {
    setCategory(urlCategory);
  }, [urlCategory]);

  const [description, setDescription] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const navigate = useNavigate();

  const sendConfirmationEmail = async () => {
    try {
      // Generate a nice email content using Gemini
      const geminiResponse = await ai.models.generateContent({
        model: "gemini-3-flash-preview",
        contents: `Write a professional and reassuring email to a student named ${profile.name} who just submitted a complaint in the category of "${category}". The email should acknowledge the receipt, mention it will be reviewed soon, and emphasize the college's commitment to safety and fairness. Return ONLY the HTML body of the email.`
      });

      const emailHtml = geminiResponse.text?.trim() || `
        <h2>Complaint Received</h2>
        <p>Dear ${profile.name},</p>
        <p>Your complaint regarding <strong>${category}</strong> has been successfully submitted and will be reviewed shortly by the relevant committee.</p>
        <p>Thank you for bringing this to our attention.</p>
      `;

      await fetch('/api/send-email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          to: profile.email,
          subject: `Complaint Received: ${category}`,
          html: emailHtml
        }),
      });
    } catch (err) {
      console.error("Failed to send notification email:", err);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!category || !description) {
      setError('Please fill in all required fields.');
      return;
    }

    setLoading(true);
    setError('');

    try {
      let filePath = '';
      
      // Upload file to Express server if present
      if (file) {
        const formData = new FormData();
        formData.append('file', file);
        
        const uploadRes = await fetch('/api/upload', {
          method: 'POST',
          body: formData,
        });
        
        if (!uploadRes.ok) throw new Error('File upload failed');
        const uploadData = await uploadRes.json();
        filePath = uploadData.filePath;
      }

      // Save complaint to Firestore
      try {
        await addDoc(collection(db, 'complaints'), {
          userId: profile.uid,
          studentName: profile.name,
          category,
          description,
          filePath,
          status: 'Pending',
          createdAt: new Date().toISOString()
        });
      } catch (error) {
        handleFirestoreError(error, OperationType.CREATE, 'complaints');
      }

      // Send confirmation email in background
      sendConfirmationEmail();

      setSuccess(true);
      setTimeout(() => navigate('/dashboard'), 2000);
    } catch (err: any) {
      setError(err.message || 'Failed to submit complaint. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div className="max-w-md mx-auto mt-20 text-center space-y-4">
        <div className="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
          <CheckCircle2 className="text-green-600 w-12 h-12" />
        </div>
        <h2 className="text-3xl font-bold text-slate-900">Complaint Submitted!</h2>
        <p className="text-slate-600">Your grievance has been recorded. You can track its status in your dashboard.</p>
        <p className="text-sm text-slate-400">Redirecting to dashboard...</p>
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto">
      <div className="bg-white p-8 md:p-12 rounded-3xl border border-slate-200 shadow-xl">
        <div className="mb-10">
          <h2 className="text-3xl font-bold text-slate-900">Lodge a Complaint</h2>
          <p className="text-slate-500 mt-2">Please provide detailed information about your grievance.</p>
        </div>

        {error && (
          <div className="mb-8 p-4 bg-red-50 border border-red-100 rounded-xl flex items-center gap-3 text-red-600">
            <AlertCircle className="w-5 h-5 shrink-0" />
            <p className="text-sm font-medium">{error}</p>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-8">
          <div className="space-y-3">
            <label className="text-sm font-bold text-slate-700 uppercase tracking-wider ml-1">Complaint Category</label>
            <select
              required
              value={category}
              onChange={(e) => setCategory(e.target.value)}
              className="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none transition-all appearance-none cursor-pointer"
            >
              <option value="">Select a category</option>
              {COMPLAINT_CATEGORIES.map(cat => (
                <option key={cat} value={cat}>{cat}</option>
              ))}
            </select>
          </div>

          <div className="space-y-3">
            <label className="text-sm font-bold text-slate-700 uppercase tracking-wider ml-1">Description</label>
            <textarea
              required
              rows={6}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Describe your complaint in detail..."
              className="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none transition-all resize-none"
            />
          </div>

          <div className="space-y-3">
            <label className="text-sm font-bold text-slate-700 uppercase tracking-wider ml-1">Supporting Document (Optional)</label>
            <div className="relative group">
              <input
                type="file"
                accept="image/*,.pdf"
                onChange={(e) => setFile(e.target.files?.[0] || null)}
                className="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
              />
              <div className="border-2 border-dashed border-slate-200 rounded-2xl p-8 text-center group-hover:border-blue-400 group-hover:bg-blue-50 transition-all">
                {file ? (
                  <div className="flex items-center justify-center gap-3 text-blue-600 font-bold">
                    <FileText className="w-6 h-6" />
                    <span>{file.name}</span>
                  </div>
                ) : (
                  <div className="space-y-2">
                    <div className="bg-slate-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto group-hover:bg-blue-100 transition-colors">
                      <Upload className="text-slate-400 group-hover:text-blue-600 w-6 h-6" />
                    </div>
                    <p className="text-slate-500 font-medium">Click or drag to upload a file</p>
                    <p className="text-xs text-slate-400">PDF, JPG, PNG (Max 5MB)</p>
                  </div>
                )}
              </div>
            </div>
          </div>

          <div className="pt-4">
            <button
              type="submit"
              disabled={loading}
              className="w-full bg-blue-600 text-white py-5 rounded-2xl font-bold text-lg hover:bg-blue-700 transition-all shadow-lg hover:shadow-blue-200 flex items-center justify-center gap-3 disabled:opacity-50"
            >
              {loading ? 'Submitting...' : (
                <>
                  <Send className="w-5 h-5" />
                  Submit Complaint
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
