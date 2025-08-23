import { Inspector } from '../constants/inspectorData';

export const getStatusColor = (status: string) => {
  switch (status) {
    case 'active': return 'bg-green-100 text-green-800';
    case 'inactive': return 'bg-gray-100 text-gray-800';
    case 'on_leave': return 'bg-yellow-100 text-yellow-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

export const getCertificationColor = (status: string) => {
  switch (status) {
    case 'valid': return 'bg-green-100 text-green-800';
    case 'expiring': return 'bg-yellow-100 text-yellow-800';
    case 'expired': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

export const getInspectionStatusColor = (status: string) => {
  switch (status) {
    case 'completed': return 'bg-green-100 text-green-800';
    case 'in_progress': return 'bg-blue-100 text-blue-800';
    case 'scheduled': return 'bg-gray-100 text-gray-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

export const filterInspectors = (
  inspectors: Inspector[], 
  searchTerm: string, 
  statusFilter: string
): Inspector[] => {
  return inspectors.filter(inspector => {
    const matchesSearch = inspector.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         inspector.department.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         inspector.specialization.some(spec => 
                           spec.toLowerCase().includes(searchTerm.toLowerCase())
                         );
    
    const matchesStatus = statusFilter === 'all' || inspector.status === statusFilter;
    
    return matchesSearch && matchesStatus;
  });
};

export const calculateInspectorStats = (inspectors: Inspector[]) => {
  const totalInspectors = inspectors.length;
  const activeInspectors = inspectors.filter(i => i.status === 'active').length;
  const avgPerformance = Math.round(inspectors.reduce((sum, i) => sum + i.performanceScore, 0) / inspectors.length);
  const expiringCertifications = inspectors.reduce((sum, inspector) => 
    sum + inspector.certifications.filter(cert => cert.status === 'expiring').length, 0
  );

  return {
    totalInspectors,
    activeInspectors,
    avgPerformance,
    expiringCertifications
  };
};