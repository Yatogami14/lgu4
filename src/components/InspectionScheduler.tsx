import React, { useState } from 'react';
import { Calendar, MapPin, Clock, User, Zap, Plus, Search, Filter } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { Badge } from './ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from './ui/avatar';
import { Textarea } from './ui/textarea';
import { Label } from './ui/label';

interface Inspector {
  id: string;
  name: string;
  specialization: string;
  workload: number;
  availability: 'available' | 'busy' | 'unavailable';
  location: string;
  rating: number;
  certifications: string[];
}

interface InspectionSchedulerProps {
  inspections: any[];
}

const mockInspectors: Inspector[] = [
  {
    id: '101',
    name: 'Juan Dela Cruz',
    specialization: 'Health & Sanitation',
    workload: 3,
    availability: 'available',
    location: 'Makati District',
    rating: 4.8,
    certifications: ['Health Inspector', 'Food Safety']
  },
  {
    id: '102',
    name: 'Anna Reyes',
    specialization: 'Fire Safety',
    workload: 5,
    availability: 'busy',
    location: 'BGC District',
    rating: 4.9,
    certifications: ['Fire Safety', 'Emergency Response']
  },
  {
    id: '103',
    name: 'Carlos Garcia',
    specialization: 'Building Safety',
    workload: 2,
    availability: 'available',
    location: 'Cebu District',
    rating: 4.7,
    certifications: ['Structural Safety', 'Building Code']
  },
  {
    id: '104',
    name: 'Maria Santos',
    specialization: 'Environmental',
    workload: 4,
    availability: 'available',
    location: 'Quezon District',
    rating: 4.6,
    certifications: ['Environmental Safety', 'Waste Management']
  }
];

export function InspectionScheduler({ inspections }: InspectionSchedulerProps) {
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [showNewInspection, setShowNewInspection] = useState(false);
  const [inspectionType, setInspectionType] = useState('');
  const [businessName, setBusinessName] = useState('');
  const [businessAddress, setBusinessAddress] = useState('');
  const [priority, setPriority] = useState('medium');
  const [notes, setNotes] = useState('');
  const [aiSuggestion, setAiSuggestion] = useState<Inspector | null>(null);

  const getAvailabilityColor = (availability: string) => {
    switch (availability) {
      case 'available': return 'bg-green-100 text-green-800';
      case 'busy': return 'bg-yellow-100 text-yellow-800';
      case 'unavailable': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const handleAIAssignment = () => {
    // AI-assisted inspector assignment logic
    const availableInspectors = mockInspectors.filter(inspector => 
      inspector.availability === 'available' && 
      inspector.specialization.toLowerCase().includes(inspectionType.toLowerCase())
    );

    if (availableInspectors.length > 0) {
      // Sort by workload (ascending) and rating (descending)
      const bestInspector = availableInspectors.sort((a, b) => {
        if (a.workload !== b.workload) return a.workload - b.workload;
        return b.rating - a.rating;
      })[0];
      
      setAiSuggestion(bestInspector);
    }
  };

  const renderCalendarView = () => {
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();

    const days = [];
    
    // Empty cells for days before the first day of the month
    for (let i = 0; i < firstDayOfMonth; i++) {
      days.push(<div key={`empty-${i}`} className="h-20 p-2"></div>);
    }

    // Days of the month
    for (let day = 1; day <= daysInMonth; day++) {
      const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const dayInspections = inspections.filter(inspection => inspection.scheduledDate === dateStr);
      const isToday = day === today.getDate();
      const isSelected = dateStr === selectedDate;

      days.push(
        <div
          key={day}
          className={`h-20 p-2 border rounded cursor-pointer transition-colors ${
            isToday ? 'bg-blue-50 border-blue-200' : ''
          } ${isSelected ? 'bg-blue-100 border-blue-300' : ''} hover:bg-gray-50`}
          onClick={() => setSelectedDate(dateStr)}
        >
          <div className="text-sm font-medium">{day}</div>
          <div className="mt-1 space-y-1">
            {dayInspections.slice(0, 2).map((inspection, idx) => (
              <div
                key={idx}
                className="text-xs p-1 bg-blue-100 text-blue-800 rounded truncate"
              >
                {inspection.businessName}
              </div>
            ))}
            {dayInspections.length > 2 && (
              <div className="text-xs text-gray-500">+{dayInspections.length - 2} more</div>
            )}
          </div>
        </div>
      );
    }

    return (
      <div className="grid grid-cols-7 gap-1">
        {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(day => (
          <div key={day} className="p-2 text-center font-medium text-gray-600 bg-gray-50">
            {day}
          </div>
        ))}
        {days}
      </div>
    );
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
        <div>
          <h2 className="text-xl sm:text-2xl font-bold">Inspection Scheduler</h2>
          <p className="text-gray-600 text-sm sm:text-base">AI-powered scheduling and inspector assignment</p>
        </div>
        <Button onClick={() => setShowNewInspection(true)} className="w-full sm:w-auto">
          <Plus className="h-4 w-4 mr-2" />
          Schedule Inspection
        </Button>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {/* Calendar View */}
        <div className="xl:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center space-x-2">
                <Calendar className="h-5 w-5" />
                <span>January 2024</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              {renderCalendarView()}
            </CardContent>
          </Card>
        </div>

        {/* Inspector Assignment Panel */}
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center space-x-2">
                <Zap className="h-5 w-5" />
                <span>AI Inspector Assignment</span>
              </CardTitle>
              <CardDescription>Optimal assignments based on workload and expertise</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {mockInspectors.map((inspector) => (
                  <div key={inspector.id} className="flex items-center justify-between p-3 border rounded-lg">
                    <div className="flex items-center space-x-3">
                      <Avatar>
                        <AvatarFallback>{inspector.name.split(' ').map(n => n[0]).join('')}</AvatarFallback>
                      </Avatar>
                      <div>
                        <p className="font-medium">{inspector.name}</p>
                        <p className="text-sm text-gray-600">{inspector.specialization}</p>
                        <p className="text-xs text-gray-500">Workload: {inspector.workload} inspections</p>
                      </div>
                    </div>
                    <div className="text-right">
                      <Badge className={getAvailabilityColor(inspector.availability)}>
                        {inspector.availability}
                      </Badge>
                      <p className="text-xs text-gray-500 mt-1">Rating: {inspector.rating}/5</p>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Daily Schedule */}
          <Card>
            <CardHeader>
              <CardTitle>Daily Schedule - {selectedDate}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {inspections
                  .filter(inspection => inspection.scheduledDate === selectedDate)
                  .map((inspection) => (
                    <div key={inspection.id} className="p-3 border rounded-lg">
                      <div className="flex items-center justify-between">
                        <div>
                          <p className="font-medium">{inspection.businessName}</p>
                          <p className="text-sm text-gray-600">{inspection.type}</p>
                          <p className="text-xs text-gray-500 flex items-center mt-1">
                            <MapPin className="h-3 w-3 mr-1" />
                            {inspection.businessAddress}
                          </p>
                        </div>
                        <Badge className={`${inspection.status === 'scheduled' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}`}>
                          {inspection.status}
                        </Badge>
                      </div>
                      <p className="text-xs text-gray-500 mt-2 flex items-center">
                        <User className="h-3 w-3 mr-1" />
                        Inspector: {inspection.inspectorName}
                      </p>
                    </div>
                  ))}
                {inspections.filter(inspection => inspection.scheduledDate === selectedDate).length === 0 && (
                  <p className="text-gray-500 text-center py-4">No inspections scheduled for this date</p>
                )}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* New Inspection Modal */}
      {showNewInspection && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <Card className="w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <CardHeader>
              <CardTitle>Schedule New Inspection</CardTitle>
              <CardDescription>AI-powered inspector assignment and scheduling</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label htmlFor="inspectionType">Inspection Type</Label>
                  <Select value={inspectionType} onValueChange={setInspectionType}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select inspection type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="health">Health & Sanitation</SelectItem>
                      <SelectItem value="fire">Fire Safety</SelectItem>
                      <SelectItem value="building">Building Safety</SelectItem>
                      <SelectItem value="environmental">Environmental</SelectItem>
                      <SelectItem value="food">Food Safety</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label htmlFor="priority">Priority Level</Label>
                  <Select value={priority} onValueChange={setPriority}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="low">Low</SelectItem>
                      <SelectItem value="medium">Medium</SelectItem>
                      <SelectItem value="high">High</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div>
                <Label htmlFor="businessName">Business Name</Label>
                <Input
                  id="businessName"
                  value={businessName}
                  onChange={(e) => setBusinessName(e.target.value)}
                  placeholder="Enter business name"
                />
              </div>

              <div>
                <Label htmlFor="businessAddress">Business Address</Label>
                <Input
                  id="businessAddress"
                  value={businessAddress}
                  onChange={(e) => setBusinessAddress(e.target.value)}
                  placeholder="Enter complete address"
                />
              </div>

              <div>
                <Label htmlFor="scheduledDate">Scheduled Date</Label>
                <Input
                  id="scheduledDate"
                  type="date"
                  value={selectedDate}
                  onChange={(e) => setSelectedDate(e.target.value)}
                />
              </div>

              <div>
                <Label htmlFor="notes">Special Notes</Label>
                <Textarea
                  id="notes"
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  placeholder="Any special instructions or requirements"
                  rows={3}
                />
              </div>

              {/* AI Inspector Suggestion */}
              {inspectionType && (
                <div className="border-t pt-4">
                  <div className="flex items-center justify-between mb-3">
                    <Label>AI Inspector Recommendation</Label>
                    <Button variant="outline" size="sm" onClick={handleAIAssignment}>
                      <Zap className="h-4 w-4 mr-2" />
                      Get AI Suggestion
                    </Button>
                  </div>
                  
                  {aiSuggestion && (
                    <div className="p-4 bg-blue-50 rounded-lg">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                          <Avatar>
                            <AvatarFallback>{aiSuggestion.name.split(' ').map(n => n[0]).join('')}</AvatarFallback>
                          </Avatar>
                          <div>
                            <p className="font-medium">{aiSuggestion.name}</p>
                            <p className="text-sm text-gray-600">{aiSuggestion.specialization}</p>
                            <p className="text-xs text-gray-500">
                              Workload: {aiSuggestion.workload} | Rating: {aiSuggestion.rating}/5
                            </p>
                          </div>
                        </div>
                        <Badge className="bg-blue-100 text-blue-800">Recommended</Badge>
                      </div>
                      <div className="mt-2 flex flex-wrap gap-1">
                        {aiSuggestion.certifications.map((cert, idx) => (
                          <Badge key={idx} variant="outline" className="text-xs">
                            {cert}
                          </Badge>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              )}

              <div className="flex justify-end space-x-3 pt-4">
                <Button variant="outline" onClick={() => setShowNewInspection(false)}>
                  Cancel
                </Button>
                <Button>
                  Schedule Inspection
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}