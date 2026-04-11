export type UserRole = 'student' | 'admin';

export interface UserProfile {
  uid: string;
  name: string;
  email: string;
  role: UserRole;
  createdAt: string;
}

export type ComplaintStatus = 'Pending' | 'In Progress' | 'Resolved';

export interface Complaint {
  id: string;
  userId: string;
  studentName: string;
  category: string;
  description: string;
  filePath?: string;
  status: ComplaintStatus;
  createdAt: string;
}

export const COMPLAINT_CATEGORIES = [
  "Sexual Cell",
  "Anti-Ragging Cell",
  "Anti-Harassment Cell",
  "Grievance Cell",
  "Hygiene/Facility Cell",
  "Disciplinary Committee"
];
