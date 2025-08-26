import React from 'react';

const CalendarSkeleton = ({ viewMode = 'month' }) => {
  if (viewMode === 'day') {
    return (
      <div className="flex-1 overflow-y-auto">
        <div className="p-4 space-y-3">
          {[1, 2, 3]?.map((i) => (
            <div key={i} className="bg-card border border-border rounded-lg p-3 animate-pulse">
              <div className="flex items-start justify-between mb-2">
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 bg-muted rounded-full"></div>
                  <div className="w-12 h-3 bg-muted rounded"></div>
                </div>
                <div className="w-16 h-3 bg-muted rounded"></div>
              </div>
              <div className="flex gap-2">
                <div className="w-12 h-12 bg-muted rounded"></div>
                <div className="flex-1 space-y-2">
                  <div className="w-full h-3 bg-muted rounded"></div>
                  <div className="w-3/4 h-3 bg-muted rounded"></div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (viewMode === 'week') {
    return (
      <div className="flex-1 overflow-y-auto">
        <div className="grid grid-cols-7 gap-px bg-border">
          {/* Week header skeleton */}
          {Array.from({ length: 7 })?.map((_, index) => (
            <div key={index} className="bg-card p-2 text-center border-b border-border">
              <div className="w-8 h-3 bg-muted rounded mx-auto mb-1"></div>
              <div className="w-4 h-4 bg-muted rounded mx-auto"></div>
            </div>
          ))}
          
          {/* Week content skeleton */}
          {Array.from({ length: 7 })?.map((_, index) => (
            <div key={index} className="bg-card min-h-[200px] p-2">
              <div className="space-y-2">
                {Array.from({ length: Math.floor(Math.random() * 3) + 1 })?.map((_, i) => (
                  <div key={i} className="bg-muted rounded-lg p-2 animate-pulse">
                    <div className="flex items-center gap-2 mb-1">
                      <div className="w-3 h-3 bg-muted-foreground/20 rounded-full"></div>
                      <div className="w-8 h-2 bg-muted-foreground/20 rounded"></div>
                    </div>
                    <div className="w-full h-2 bg-muted-foreground/20 rounded"></div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  // Month view skeleton
  const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
  
  return (
    <div className="flex-1 overflow-y-auto">
      <div className="grid grid-cols-7 gap-px bg-border">
        {/* Month header */}
        {weekDays?.map((day) => (
          <div key={day} className="bg-card p-3 text-center border-b border-border">
            <span className="text-xs font-body text-muted-foreground font-medium">
              {day}
            </span>
          </div>
        ))}
        
        {/* Month content skeleton */}
        {Array.from({ length: 35 })?.map((_, index) => (
          <div key={index} className="bg-card min-h-[120px] p-2">
            <div className="flex items-center justify-between mb-2">
              <div className="w-4 h-4 bg-muted rounded animate-pulse"></div>
              {Math.random() > 0.7 && (
                <div className="w-4 h-4 bg-accent/20 rounded-full animate-pulse"></div>
              )}
            </div>
            
            <div className="space-y-1">
              {Array.from({ length: Math.floor(Math.random() * 3) })?.map((_, i) => (
                <div key={i} className="bg-muted rounded p-2 animate-pulse">
                  <div className="flex items-center gap-1 mb-1">
                    <div className="w-2 h-2 bg-muted-foreground/20 rounded-full"></div>
                    <div className="w-6 h-2 bg-muted-foreground/20 rounded"></div>
                  </div>
                  <div className="w-full h-2 bg-muted-foreground/20 rounded"></div>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default CalendarSkeleton;