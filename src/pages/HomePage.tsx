import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { COMPLAINT_CATEGORIES } from '../types';
import { ShieldAlert, Users, HeartHandshake, Scale, Building2, Gavel, ArrowRight, Info, CheckCircle2 } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

const categoryIcons: Record<string, React.ReactNode> = {
  "Anti-Sexual Harassment Cell": <ShieldAlert className="w-8 h-8 text-rose-500" />,
  "Anti-Ragging Cell": <Users className="w-8 h-8 text-orange-500" />,
  "Anti-Harassment Cell": <HeartHandshake className="w-8 h-8 text-purple-500" />,
  "Grievance Cell": <Scale className="w-8 h-8 text-blue-500" />,
  "Hygiene/Facility Cell": <Building2 className="w-8 h-8 text-emerald-500" />,
  "Disciplinary Committee": <Gavel className="w-8 h-8 text-slate-700" />
};

const categoryPolicies: Record<string, { description: string, guidelines: string[] }> = {
  "General": {
    description: "Complaints should be submitted when you experience or witness any behavior that violates college policies or affects your well-being.",
    guidelines: [
      "Provide clear and factual descriptions.",
      "Upload supporting documents if available.",
      "Maintain confidentiality of the process.",
      "False complaints may lead to disciplinary action.",
      "Track status via your dashboard."
    ]
  },
  "Anti-Sexual Harassment Cell": {
    description: "Handles cases related to sexual harassment, misconduct, or any gender-based discrimination.",
    guidelines: [
      "Strict confidentiality is maintained for the complainant.",
      "Immediate interim relief measures can be requested.",
      "Support from counselors is available upon request.",
      "Investigation follows the Vishaka Guidelines and POSH Act."
    ]
  },
  "Anti-Ragging Cell": {
    description: "Strict zero-tolerance policy towards any form of ragging on or off campus.",
    guidelines: [
      "Ragging is a criminal offense as per Supreme Court orders.",
      "Identity of the whistleblower will be kept secret.",
      "Immediate suspension of accused during investigation.",
      "Severe penalties including expulsion and police FIR."
    ]
  },
  "Anti-Harassment Cell": {
    description: "Deals with bullying, mental harassment, or discriminatory behavior by peers or staff.",
    guidelines: [
      "Document specific instances with dates and times.",
      "Mediation services are offered if appropriate.",
      "Protection against retaliation is guaranteed.",
      "Fair hearing for both parties involved."
    ]
  },
  "Grievance Cell": {
    description: "For academic issues, examination concerns, or administrative delays.",
    guidelines: [
      "Specify the department or staff member involved.",
      "Attach relevant academic records or correspondence.",
      "Resolution timeline: usually within 7-10 working days.",
      "Appeal process available if not satisfied with outcome."
    ]
  },
  "Hygiene/Facility Cell": {
    description: "Concerns regarding canteen food, washroom cleanliness, or campus infrastructure.",
    guidelines: [
      "Photos of the facility issue are highly recommended.",
      "Specify the exact location (Block, Floor, Room).",
      "Routine inspections are triggered by valid complaints.",
      "Feedback on resolved issues is encouraged."
    ]
  },
  "Disciplinary Committee": {
    description: "Handles violations of the student code of conduct and campus rules.",
    guidelines: [
      "Evidence of rule violation must be provided.",
      "Students have the right to represent their case.",
      "Disciplinary actions range from warnings to fines.",
      "Parents may be notified depending on severity."
    ]
  }
};

export default function HomePage() {
  const [selectedCategory, setSelectedCategory] = useState<string>("General");

  const currentPolicy = categoryPolicies[selectedCategory] || categoryPolicies["General"];

  return (
    <div className="space-y-16">
      {/* Hero Section */}
      <section className="text-center max-w-3xl mx-auto space-y-6 py-12">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
        >
          <h2 className="text-5xl font-extrabold tracking-tight text-slate-900 sm:text-6xl">
            Student <span className="text-blue-600">CMS</span>
          </h2>
          <p className="mt-6 text-lg leading-8 text-slate-600">
            A secure and transparent platform for students to voice their concerns and grievances to the college administration.
          </p>
        </motion.div>
      </section>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-12 items-start">
        {/* Categories Grid */}
        <div className="lg:col-span-2 space-y-8">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {COMPLAINT_CATEGORIES.map((category, index) => (
              <motion.div
                key={category}
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: index * 0.1 }}
                onClick={() => setSelectedCategory(category)}
                onMouseOver={() => setSelectedCategory(category)}
                className={`p-6 rounded-2xl border transition-all group cursor-pointer ${
                  selectedCategory === category 
                    ? 'bg-blue-50 border-blue-400 shadow-md ring-2 ring-blue-200' 
                    : 'bg-white border-slate-200 shadow-sm hover:shadow-md'
                }`}
              >
                <div className={`mb-4 w-14 h-14 rounded-xl flex items-center justify-center transition-colors ${
                  selectedCategory === category ? 'bg-white' : 'bg-slate-50 group-hover:bg-blue-50'
                }`}>
                  {categoryIcons[category]}
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2">{category}</h3>
                <p className="text-sm text-slate-500">Click to view specific guidelines for {category.toLowerCase()}.</p>
              </motion.div>
            ))}
          </div>
        </div>

        {/* Policy Section */}
        <aside id="policy" className="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm sticky top-24 min-h-[400px]">
          <div className="space-y-6">
            <div className="flex items-center gap-2 mb-2 text-blue-600">
              <Info className="w-6 h-6" />
              <h3 className="text-xl font-bold text-slate-900">
                {selectedCategory === "General" ? "Policy & Guidelines" : `${selectedCategory} Policy`}
              </h3>
            </div>
            
            <div>
              <h4 className="font-bold text-slate-800 mb-2">
                {selectedCategory === "General" ? "When to submit?" : "About this cell"}
              </h4>
              <p className="text-sm text-slate-600 leading-relaxed">
                {currentPolicy.description}
              </p>
            </div>

            <div className="space-y-3">
              <h4 className="font-bold text-slate-800 mb-2">Specific Guidelines</h4>
              {currentPolicy.guidelines.map((item, i) => (
                <div key={i} className="flex gap-3 items-start">
                  <CheckCircle2 className="w-5 h-5 text-blue-500 shrink-0 mt-0.5" />
                  <p className="text-sm text-slate-600">{item}</p>
                </div>
              ))}
            </div>

            <div className="pt-4 flex flex-col gap-4">
              <Link 
                to="/lodge-complaint" 
                className="w-full bg-blue-600 text-white py-3 rounded-xl font-bold text-center hover:bg-blue-700 transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2"
              >
                Lodge Complaint
                <ArrowRight className="w-4 h-4" />
              </Link>

              {selectedCategory !== "General" && (
                <button 
                  onClick={() => setSelectedCategory("General")}
                  className="text-xs font-bold text-blue-600 hover:underline self-start"
                >
                  ← Back to General Guidelines
                </button>
              )}
            </div>

            <div className="pt-4 border-t border-slate-100">
              <p className="text-xs text-slate-400 italic">
                All complaints are handled with strict confidentiality by the respective committees.
              </p>
            </div>
          </div>
        </aside>
      </div>
    </div>
  );
}
