import React from 'react';
import PostCard from './PostCard';

const CalendarGrid = ({ 
  currentDate, 
  viewMode, 
  posts, 
  onPostClick, 
  onEmptySlotClick 
}) => {
  const getDaysInMonth = (date) => {
    const year = date?.getFullYear();
    const month = date?.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay?.getDate();
    const startingDayOfWeek = firstDay?.getDay();

    const days = [];
    
    // Add empty cells for days before the first day of the month
    for (let i = 0; i < startingDayOfWeek; i++) {
      days?.push(null);
    }
    
    // Add all days of the month
    for (let day = 1; day <= daysInMonth; day++) {
      days?.push(new Date(year, month, day));
    }
    
    return days;
  };

  const getWeekDays = (date) => {
    const startOfWeek = new Date(date);
    let day = startOfWeek?.getDay();
    startOfWeek?.setDate(startOfWeek?.getDate() - day);
    
    const days = [];
    for (let i = 0; i < 7; i++) {
      let day = new Date(startOfWeek);
      day?.setDate(startOfWeek?.getDate() + i);
      days?.push(day);
    }
    
    return days;
  };

  const getPostsForDate = (date) => {
    if (!date) return [];
    
    const dateStr = date?.toDateString();
    return posts?.filter(post => {
      const postDate = new Date(post.scheduledTime);
      return postDate?.toDateString() === dateStr;
    });
  };

  const isToday = (date) => {
    if (!date) return false;
    const today = new Date();
    return date?.toDateString() === today?.toDateString();
  };

  const isCurrentMonth = (date) => {
    if (!date) return false;
    return date?.getMonth() === currentDate?.getMonth();
  };

  const formatDayNumber = (date) => {
    return date?.getDate();
  };

  const formatWeekDay = (date) => {
    return date?.toLocaleDateString('pt-BR', { 
      weekday: 'short',
      timeZone: 'America/Sao_Paulo'
    });
  };

  if (viewMode === 'day') {
    const dayPosts = getPostsForDate(currentDate);
    
    return (
      <div className="flex-1 overflow-y-auto">
        <div className="p-4 space-y-3">
          {dayPosts?.length > 0 ? (
            dayPosts?.map((post) => (
              <PostCard
                key={post?.id}
                post={post}
                onClick={onPostClick}
                viewMode="day"
              />
            ))
          ) : (
            <div 
              onClick={() => onEmptySlotClick(currentDate)}
              className="border-2 border-dashed border-border rounded-lg p-8 text-center cursor-pointer hover:border-accent/50 transition-smooth"
            >
              <div className="text-muted-foreground">
                <div className="text-2xl mb-2">📅</div>
                <p className="font-body text-sm mb-1">Nenhum post agendado</p>
                <p className="font-body text-xs">Toque para criar um novo post</p>
              </div>
            </div>
          )}
        </div>
      </div>
    );
  }

  if (viewMode === 'week') {
    const weekDays = getWeekDays(currentDate);
    
    return (
      <div className="flex-1 overflow-y-auto">
        <div className="grid grid-cols-7 gap-px bg-border">
          {/* Week header */}
          {weekDays?.map((day, index) => (
            <div key={index} className="bg-card p-2 text-center border-b border-border">
              <div className="text-xs font-body text-muted-foreground mb-1">
                {formatWeekDay(day)}
              </div>
              <div className={`text-sm font-body font-medium ${
                isToday(day) ? 'text-accent' : 'text-foreground'
              }`}>
                {formatDayNumber(day)}
              </div>
            </div>
          ))}
          
          {/* Week content */}
          {weekDays?.map((day, index) => {
            const dayPosts = getPostsForDate(day);
            
            return (
              <div key={index} className="bg-card min-h-[200px] p-2">
                <div className="space-y-2">
                  {dayPosts?.map((post) => (
                    <PostCard
                      key={post?.id}
                      post={post}
                      onClick={onPostClick}
                      viewMode="week"
                    />
                  ))}
                  
                  {dayPosts?.length === 0 && (
                    <div 
                      onClick={() => onEmptySlotClick(day)}
                      className="border border-dashed border-border rounded p-2 text-center cursor-pointer hover:border-accent/50 transition-smooth opacity-0 hover:opacity-100"
                    >
                      <span className="text-xs text-muted-foreground font-body">
                        Adicionar
                      </span>
                    </div>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    );
  }

  // Month view
  const monthDays = getDaysInMonth(currentDate);
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
        
        {/* Month content */}
        {monthDays?.map((day, index) => {
          const dayPosts = getPostsForDate(day);
          const isCurrentMonthDay = isCurrentMonth(day);
          
          return (
            <div 
              key={index} 
              className={`bg-card min-h-[120px] p-2 ${
                !isCurrentMonthDay ? 'opacity-30' : ''
              }`}
            >
              {day && (
                <>
                  <div className="flex items-center justify-between mb-2">
                    <span className={`text-sm font-body font-medium ${
                      isToday(day) ? 'text-accent' : 'text-foreground'
                    }`}>
                      {formatDayNumber(day)}
                    </span>
                    
                    {dayPosts?.length > 0 && (
                      <span className="text-xs bg-accent text-accent-foreground rounded-full px-1.5 py-0.5 font-body">
                        {dayPosts?.length}
                      </span>
                    )}
                  </div>
                  
                  <div className="space-y-1">
                    {dayPosts?.slice(0, 2)?.map((post) => (
                      <PostCard
                        key={post?.id}
                        post={post}
                        onClick={onPostClick}
                        viewMode="month"
                      />
                    ))}
                    
                    {dayPosts?.length > 2 && (
                      <div className="text-xs text-muted-foreground text-center py-1 font-body">
                        +{dayPosts?.length - 2} mais
                      </div>
                    )}
                    
                    {dayPosts?.length === 0 && isCurrentMonthDay && (
                      <div 
                        onClick={() => onEmptySlotClick(day)}
                        className="border border-dashed border-border rounded p-2 text-center cursor-pointer hover:border-accent/50 transition-smooth opacity-0 hover:opacity-100"
                      >
                        <span className="text-xs text-muted-foreground font-body">
                          +
                        </span>
                      </div>
                    )}
                  </div>
                </>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default CalendarGrid;