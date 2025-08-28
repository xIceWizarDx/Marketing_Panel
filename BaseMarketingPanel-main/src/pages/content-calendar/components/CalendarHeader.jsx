import React from 'react';
import Icon from '../../../components/AppIcon';
import Button from '../../../components/ui/Button';

const CalendarHeader = ({ 
  currentDate, 
  viewMode, 
  onViewModeChange, 
  onDateChange, 
  onTodayClick,
  onNewPostClick 
}) => {
  const formatHeaderDate = () => {
    const options = { 
      year: 'numeric', 
      month: 'long',
      timeZone: 'America/Sao_Paulo'
    };
    
    if (viewMode === 'day') {
      return currentDate?.toLocaleDateString('pt-BR', {
        ...options,
        day: 'numeric',
        weekday: 'long'
      });
    }
    
    return currentDate?.toLocaleDateString('pt-BR', options);
  };

  const navigateDate = (direction) => {
    const newDate = new Date(currentDate);
    
    switch (viewMode) {
      case 'day':
        newDate?.setDate(newDate?.getDate() + direction);
        break;
      case 'week':
        newDate?.setDate(newDate?.getDate() + (direction * 7));
        break;
      case 'month':
        newDate?.setMonth(newDate?.getMonth() + direction);
        break;
    }
    
    onDateChange(newDate);
  };

  return (
    <div className="bg-card border-b border-border p-4 lg:p-6">
      {/* Mobile Header */}
      <div className="flex items-center justify-between mb-4 lg:hidden">
        <Button
          variant="outline"
          size="sm"
          onClick={onNewPostClick}
          iconName="Plus"
          iconPosition="left"
          className="flex-shrink-0"
        >
          Novo Post
        </Button>
        
        <div className="flex items-center gap-1">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => navigateDate(-1)}
            aria-label="Data anterior"
          >
            <Icon name="ChevronLeft" size={20} />
          </Button>
          
          <Button
            variant="ghost"
            size="sm"
            onClick={onTodayClick}
            className="text-xs px-2"
          >
            Hoje
          </Button>
          
          <Button
            variant="ghost"
            size="icon"
            onClick={() => navigateDate(1)}
            aria-label="Próxima data"
          >
            <Icon name="ChevronRight" size={20} />
          </Button>
        </div>
      </div>
      {/* Date Display */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-4">
          <h1 className="font-body font-bold text-xl lg:text-2xl text-primary">
            {formatHeaderDate()}
          </h1>
          
          {/* Desktop Navigation */}
          <div className="hidden lg:flex items-center gap-1">
            <Button
              variant="ghost"
              size="icon"
              onClick={() => navigateDate(-1)}
              aria-label="Data anterior"
            >
              <Icon name="ChevronLeft" size={20} />
            </Button>
            
            <Button
              variant="ghost"
              size="sm"
              onClick={onTodayClick}
              className="text-sm px-3"
            >
              Hoje
            </Button>
            
            <Button
              variant="ghost"
              size="icon"
              onClick={() => navigateDate(1)}
              aria-label="Próxima data"
            >
              <Icon name="ChevronRight" size={20} />
            </Button>
          </div>
        </div>

        {/* Desktop New Post Button */}
        <Button
          variant="default"
          onClick={onNewPostClick}
          iconName="Plus"
          iconPosition="left"
          className="hidden lg:flex"
        >
          Novo Post
        </Button>
      </div>
      {/* View Mode Toggle */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-1 bg-muted rounded-lg p-1">
          {['day', 'week', 'month']?.map((mode) => (
            <Button
              key={mode}
              variant={viewMode === mode ? 'default' : 'ghost'}
              size="sm"
              onClick={() => onViewModeChange(mode)}
              className="text-xs px-3 py-1.5"
            >
              {mode === 'day' ? 'Dia' : mode === 'week' ? 'Semana' : 'Mês'}
            </Button>
          ))}
        </div>

        {/* Filter Button */}
        <Button
          variant="outline"
          size="sm"
          iconName="Filter"
          iconPosition="left"
          className="text-xs"
        >
          Filtros
        </Button>
      </div>
    </div>
  );
};

export default CalendarHeader;