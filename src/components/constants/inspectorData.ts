export interface Inspector {
  id: string;
  name: string;
  email: string;
  phone: string;
  department: string;
  specialization: string[];
  certifications: Certification[];
  performanceScore: number;
  totalInspections: number;
  accuracyRate: number;
  workload: number;
  status: 'active' | 'inactive' | 'on_leave';
  joinDate: string;
  lastInspection: string;
  location: string;
  recentInspections: RecentInspection[];
}

export interface Certification {
  id: string;
  name: string;
  issuer: string;
  issueDate: string;
  expiryDate: string;
  status: 'valid' | 'expiring' | 'expired';
}

export interface RecentInspection {
  id: string;
  businessName: string;
  type: string;
  date: string;
  score: number;
  violations: number;
  status: 'completed' | 'in_progress' | 'scheduled';
}

export const mockInspectors: Inspector[] = [
  {
    id: '101',
    name: 'Juan Dela Cruz',
    email: 'juan.delacruz@lgu.gov.ph',
    phone: '+63 917 123 4567',
    department: 'Health and Safety Division',
    specialization: ['Health & Sanitation', 'Food Safety'],
    performanceScore: 92,
    totalInspections: 156,
    accuracyRate: 94,
    workload: 3,
    status: 'active',
    joinDate: '2022-03-15',
    lastInspection: '2024-01-15',
    location: 'Makati District',
    certifications: [
      {
        id: '1',
        name: 'Certified Health Inspector',
        issuer: 'Department of Health',
        issueDate: '2022-01-15',
        expiryDate: '2025-01-15',
        status: 'valid'
      },
      {
        id: '2',
        name: 'Food Safety Certification',
        issuer: 'FDA Philippines',
        issueDate: '2023-06-01',
        expiryDate: '2024-06-01',
        status: 'expiring'
      }
    ],
    recentInspections: [
      {
        id: '1',
        businessName: 'ABC Restaurant',
        type: 'Health & Sanitation',
        date: '2024-01-15',
        score: 85,
        violations: 1,
        status: 'completed'
      },
      {
        id: '2',
        businessName: 'Food Court Plaza',
        type: 'Food Safety',
        date: '2024-01-14',
        score: 90,
        violations: 0,
        status: 'completed'
      }
    ]
  },
  {
    id: '102',
    name: 'Anna Reyes',
    email: 'anna.reyes@lgu.gov.ph',
    phone: '+63 918 987 6543',
    department: 'Fire Safety Division',
    specialization: ['Fire Safety', 'Emergency Response'],
    performanceScore: 96,
    totalInspections: 203,
    accuracyRate: 98,
    workload: 5,
    status: 'active',
    joinDate: '2021-08-22',
    lastInspection: '2024-01-16',
    location: 'BGC District',
    certifications: [
      {
        id: '1',
        name: 'Fire Safety Inspector',
        issuer: 'Bureau of Fire Protection',
        issueDate: '2021-07-01',
        expiryDate: '2024-07-01',
        status: 'valid'
      },
      {
        id: '2',
        name: 'Emergency Response Coordinator',
        issuer: 'NDRRMC',
        issueDate: '2022-03-15',
        expiryDate: '2025-03-15',
        status: 'valid'
      }
    ],
    recentInspections: [
      {
        id: '1',
        businessName: 'XYZ Mall',
        type: 'Fire Safety',
        date: '2024-01-16',
        score: 72,
        violations: 2,
        status: 'completed'
      }
    ]
  },
  {
    id: '103',
    name: 'Carlos Garcia',
    email: 'carlos.garcia@lgu.gov.ph',
    phone: '+63 919 555 1234',
    department: 'Building Safety Division',
    specialization: ['Building Safety', 'Structural Assessment'],
    performanceScore: 88,
    totalInspections: 124,
    accuracyRate: 91,
    workload: 2,
    status: 'active',
    joinDate: '2023-01-10',
    lastInspection: '2024-01-14',
    location: 'Cebu District',
    certifications: [
      {
        id: '1',
        name: 'Building Safety Inspector',
        issuer: 'DPWH',
        issueDate: '2023-01-01',
        expiryDate: '2026-01-01',
        status: 'valid'
      }
    ],
    recentInspections: [
      {
        id: '1',
        businessName: 'Tech Hub Office',
        type: 'Building Safety',
        date: '2024-01-14',
        score: 95,
        violations: 0,
        status: 'completed'
      }
    ]
  }
];