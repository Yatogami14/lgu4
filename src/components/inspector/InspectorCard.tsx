import React from 'react';
import { Card, CardContent } from '../ui/card';
import { Badge } from '../ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '../ui/avatar';
import { Inspector } from '../constants/inspectorData';
import { getStatusColor } from '../utils/inspectorUtils';

interface InspectorCardProps {
  inspector: Inspector;
  onClick: (inspector: Inspector) => void;
}

export function InspectorCard({ inspector, onClick }: InspectorCardProps) {
  return (
    <Card className="hover:shadow-md transition-shadow cursor-pointer" onClick={() => onClick(inspector)}>
      <CardContent className="pt-6">
        <div className="flex items-start justify-between mb-4">
          <div className="flex items-center space-x-4">
            <Avatar className="h-16 w-16">
              <AvatarImage src={inspector.email} />
              <AvatarFallback>{inspector.name.split(' ').map(n => n[0]).join('')}</AvatarFallback>
            </Avatar>
            <div>
              <h3 className="font-semibold text-lg">{inspector.name}</h3>
              <p className="text-gray-600">{inspector.department}</p>
              <div className="flex items-center space-x-2 mt-2">
                <Badge className={getStatusColor(inspector.status)}>
                  {inspector.status.replace('_', ' ')}
                </Badge>
                <Badge variant="outline">
                  {inspector.workload} active
                </Badge>
              </div>
            </div>
          </div>
          
          <div className="text-right">
            <div className="text-2xl font-bold text-green-600 mb-1">
              {inspector.performanceScore}%
            </div>
            <p className="text-sm text-gray-600">Performance Score</p>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-4 text-sm mb-4">
          <div>
            <p className="text-gray-500">Total Inspections</p>
            <p className="font-medium">{inspector.totalInspections}</p>
          </div>
          <div>
            <p className="text-gray-500">Accuracy Rate</p>
            <p className="font-medium">{inspector.accuracyRate}%</p>
          </div>
          <div>
            <p className="text-gray-500">Last Inspection</p>
            <p className="font-medium">{new Date(inspector.lastInspection).toLocaleDateString()}</p>
          </div>
          <div>
            <p className="text-gray-500">Location</p>
            <p className="font-medium">{inspector.location}</p>
          </div>
        </div>

        <div className="border-t pt-3">
          <p className="text-xs text-gray-500 mb-2">Specializations</p>
          <div className="flex flex-wrap gap-1">
            {inspector.specialization.map((spec, idx) => (
              <Badge key={idx} variant="outline" className="text-xs">
                {spec}
              </Badge>
            ))}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}