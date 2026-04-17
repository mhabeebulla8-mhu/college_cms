import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { createUserWithEmailAndPassword } from 'firebase/auth';
import { doc, setDoc } from 'firebase/firestore';
import { auth, db } from '../lib/firebase';
import { UserPlus, Mail, Lock, User, AlertCircle, ShieldCheck, Hash, FileUp, Loader2 } from 'lucide-react';
import { GoogleGenAI } from "@google/genai";

const ai = new GoogleGenAI({ apiKey: process.env.GEMINI_API_KEY });

export default function RegisterPage() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [universityRegNo, setUniversityRegNo] = useState('');
  const [idCard, setIdCard] = useState<File | null>(null);
  const [role] = useState<'student' | 'admin'>('student');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [verifying, setVerifying] = useState(false);
  const navigate = useNavigate();

  const fileToBase64 = (file: File): Promise<string> => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.readAsDataURL(file);
      reader.onload = () => {
        const base64 = (reader.result as string).split(',')[1];
        resolve(base64);
      };
      reader.onerror = error => reject(error);
    });
  };

  const verifyIdCard = async (file: File): Promise<boolean> => {
    try {
      setVerifying(true);
      const base64Data = await fileToBase64(file);
      
      const response = await ai.models.generateContent({
        model: "gemini-3-flash-preview",
        contents: {
          parts: [
            {
              inlineData: {
                data: base64Data,
                mimeType: file.type
              }
            },
            {
              text: "Does this ID card belong to 'AL AMEEN INSTITUTE OF INFORMATION SCIENCES'? Answer only 'YES' or 'NO'. If the text is clearly visible and matches, say YES. If not, say NO."
            }
          ]
        }
      });

      return response.text?.trim().toUpperCase() === 'YES';
    } catch (err) {
      console.error('Verification error:', err);
      return false;
    } finally {
      setVerifying(false);
    }
  };

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    if (idCard) {
      const isValid = await verifyIdCard(idCard);
      if (!isValid) {
        setError("Invalid ID Card. This platform only accepts ID cards from 'AL AMEEN INSTITUTE OF INFORMATION SCIENCES'. Please upload a valid ID card.");
        setLoading(false);
        return;
      }
    }

    try {
      let idCardPath = '';

      // Upload ID Card if provided
      if (idCard) {
        const formData = new FormData();
        formData.append('file', idCard);
        
        const uploadRes = await fetch('/api/upload', {
          method: 'POST',
          body: formData,
        });
        
        if (uploadRes.ok) {
          const data = await uploadRes.json();
          idCardPath = data.filePath;
        }
      }

      const { user } = await createUserWithEmailAndPassword(auth, email, password);
      
      // Create user profile in Firestore
      await setDoc(doc(db, 'users', user.uid), {
        uid: user.uid,
        name,
        email,
        role: 'student',
        universityRegNo,
        idCardPath,
        createdAt: new Date().toISOString()
      });

      navigate('/');
    } catch (err: any) {
      setError(err.message || 'Failed to register. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-md mx-auto mt-8 mb-12">
      <div className="bg-white p-8 rounded-3xl border border-slate-200 shadow-xl">
        <div className="text-center mb-8">
          <div className="bg-blue-50 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <UserPlus className="text-blue-600 w-8 h-8" />
          </div>
          <h2 className="text-2xl font-bold text-slate-900">Create Account</h2>
          <p className="text-slate-500 text-sm mt-1">Join the College Complaint Management System</p>
        </div>

        {error && (
          <div className="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl flex items-center gap-3 text-red-600 text-sm">
            <AlertCircle className="w-5 h-5 shrink-0" />
            <p>{error}</p>
          </div>
        )}

        <form onSubmit={handleRegister} className="space-y-4">
          <div className="space-y-2">
            <label className="text-sm font-semibold text-slate-700 ml-1">Full Name</label>
            <div className="relative">
              <User className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
              <input 
                type="text" 
                required
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                placeholder=""
              />
            </div>
          </div>

          <div className="space-y-2">
            <label className="text-sm font-semibold text-slate-700 ml-1">Email Address</label>
            <div className="relative">
              <Mail className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
              <input 
                type="email" 
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                placeholder=""
              />
            </div>
          </div>

          {role === 'student' && (
            <>
              <div className="space-y-2">
                <label className="text-sm font-semibold text-slate-700 ml-1">University Reg No</label>
                <div className="relative">
                  <Hash className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                  <input 
                    type="text" 
                    required={role === 'student'}
                    value={universityRegNo}
                    onChange={(e) => setUniversityRegNo(e.target.value)}
                    className="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                    placeholder=""
                  />
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-semibold text-slate-700 ml-1">Upload ID Card</label>
                <div className="relative">
                  <FileUp className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                  <input 
                    type="file" 
                    accept="image/*,.pdf"
                    required={role === 'student'}
                    onChange={(e) => setIdCard(e.target.files?.[0] || null)}
                    className="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none file:mr-4 file:py-1 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                  />
                </div>
              </div>
            </>
          )}

          <div className="space-y-2">
            <label className="text-sm font-semibold text-slate-700 ml-1">Password</label>
            <div className="relative">
              <Lock className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
              <input 
                type="password" 
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                placeholder=""
              />
            </div>
          </div>

          <button 
            type="submit"
            disabled={loading || verifying}
            className="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg hover:shadow-blue-200 disabled:opacity-50 disabled:cursor-not-allowed mt-4 flex items-center justify-center gap-2"
          >
            {verifying ? (
              <>
                <Loader2 className="w-5 h-5 animate-spin" />
                Verifying ID Card...
              </>
            ) : loading ? 'Creating Account...' : 'Register'}
          </button>
        </form>

        <div className="mt-8 text-center text-sm text-slate-500">
          Already have an account?{' '}
          <Link to="/login" className="text-blue-600 font-bold hover:underline">Sign In</Link>
        </div>
      </div>
    </div>
  );
}
