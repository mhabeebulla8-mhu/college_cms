import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate, Link, useNavigate } from 'react-router-dom';
import { onAuthStateChanged, signOut, User } from 'firebase/auth';
import { doc, getDoc, setDoc } from 'firebase/firestore';
import { auth, db, handleFirestoreError, OperationType } from './lib/firebase';
import { UserProfile } from './types';
import { LogOut, User as UserIcon, Shield, FileText, Home as HomeIcon, Info } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import ErrorBoundary from './components/ErrorBoundary';

// Pages
import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import StudentDashboard from './pages/StudentDashboard';
import AdminDashboard from './pages/AdminDashboard';
import ComplaintForm from './pages/ComplaintForm';

export default function App() {
  const [user, setUser] = useState<User | null>(null);
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (firebaseUser) => {
      setUser(firebaseUser);
      if (firebaseUser) {
        const path = `users/${firebaseUser.uid}`;
        try {
          const docRef = doc(db, 'users', firebaseUser.uid);
          const docSnap = await getDoc(docRef);
          if (docSnap.exists()) {
            setProfile(docSnap.data() as UserProfile);
          }
        } catch (error) {
          handleFirestoreError(error, OperationType.GET, path);
        }
      } else {
        setProfile(null);
      }
      setLoading(false);
    });

    return () => unsubscribe();
  }, []);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <ErrorBoundary>
      <Router>
        <div className="min-h-screen bg-slate-50 font-sans text-slate-900">
          <Navbar user={user} profile={profile} />
          <main className="container mx-auto px-4 py-8">
            <Routes>
              <Route path="/" element={<HomePage />} />
              <Route path="/login" element={!user ? <LoginPage /> : <Navigate to={profile?.role === 'admin' ? '/admin' : '/dashboard'} />} />
              <Route path="/register" element={!user ? <RegisterPage /> : <Navigate to="/dashboard" />} />
              
              <Route 
                path="/dashboard" 
                element={user && profile?.role === 'student' ? <StudentDashboard profile={profile} /> : <Navigate to="/login" />} 
              />
              <Route 
                path="/admin" 
                element={user && profile?.role === 'admin' ? <AdminDashboard profile={profile} /> : <Navigate to="/login" />} 
              />
              <Route 
                path="/lodge-complaint" 
                element={user && profile?.role === 'student' ? <ComplaintForm profile={profile} /> : <Navigate to="/login" />} 
              />
            </Routes>
          </main>
          <Footer />
        </div>
      </Router>
    </ErrorBoundary>
  );
}

function Navbar({ user, profile }: { user: User | null, profile: UserProfile | null }) {
  const navigate = useNavigate();

  const handleLogout = async () => {
    await signOut(auth);
    navigate('/');
  };

  return (
    <nav className="bg-white border-b border-slate-200 sticky top-0 z-50">
      <div className="container mx-auto px-4 h-16 flex items-center justify-between">
        <Link to="/" className="flex items-center gap-2">
          <div className="bg-blue-600 p-2 rounded-lg">
            <Shield className="text-white w-6 h-6" />
          </div>
          <div>
            <h1 className="font-bold text-lg leading-tight text-slate-900">MSc/BCA College</h1>
            <p className="text-xs text-slate-500 font-medium uppercase tracking-wider">Student CMS</p>
          </div>
        </Link>

        <div className="hidden md:flex items-center gap-8">
          <Link to="/" className="text-sm font-medium text-slate-600 hover:text-blue-600 transition-colors">Home</Link>
          <a href="#policy" className="text-sm font-medium text-slate-600 hover:text-blue-600 transition-colors">Policy</a>
          
          {user ? (
            <div className="flex items-center gap-4">
              <Link 
                to={profile?.role === 'admin' ? '/admin' : '/dashboard'} 
                className="text-sm font-medium text-slate-600 hover:text-blue-600 flex items-center gap-1"
              >
                <UserIcon className="w-4 h-4" />
                Dashboard
              </Link>
              <button 
                onClick={handleLogout}
                className="flex items-center gap-1 text-sm font-medium text-red-600 hover:text-red-700 transition-colors"
              >
                <LogOut className="w-4 h-4" />
                Logout
              </button>
            </div>
          ) : (
            <Link 
              to="/login" 
              className="bg-blue-600 text-white px-5 py-2 rounded-full text-sm font-semibold hover:bg-blue-700 transition-all shadow-sm hover:shadow-md"
            >
              Login
            </Link>
          )}
        </div>
      </div>
    </nav>
  );
}

function Footer() {
  return (
    <footer className="bg-white border-t border-slate-200 py-8 mt-auto">
      <div className="container mx-auto px-4 text-center">
        <p className="text-sm text-slate-500">© 2026 MSc/BCA College Institute. All rights reserved.</p>
        <div className="flex justify-center gap-4 mt-2">
          <Link to="/" className="text-xs text-slate-400 hover:text-blue-600">Privacy Policy</Link>
          <Link to="/" className="text-xs text-slate-400 hover:text-blue-600">Terms of Service</Link>
        </div>
      </div>
    </footer>
  );
}
