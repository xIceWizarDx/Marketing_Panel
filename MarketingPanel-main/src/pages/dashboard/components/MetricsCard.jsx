import React from 'react';
import Icon from '../../../components/AppIcon';

const MetricsCard = ({ 
  title, 
  value, 
  format = 'number', 
  icon, 
  trend, 
  description, 
  compact = false 
}) => {
  const formatValue = (val, fmt) => {
    if (val === null || val === undefined) return '--';
    
    switch (fmt) {
      case 'currency':
        return `R$ ${val?.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
      case 'percentage':
        return `${val}%`;
      case 'number':
        if (val >= 1000000) {
          return `${(val / 1000000)?.toFixed(1)}M`;
        }
        if (val >= 1000) {
          return `${(val / 1000)?.toFixed(1)}K`;
        }
        return val?.toLocaleString('pt-BR');
      default:
        return val?.toString();
    }
  };

  const getTrendColor = (trendValue) => {
    if (!trendValue) return '';
    const isPositive = trendValue?.startsWith('+');
    return isPositive ? 'text-success' : 'text-error';
  };

  const getTrendIcon = (trendValue) => {
    if (!trendValue) return null;
    const isPositive = trendValue?.startsWith('+');
    return isPositive ? 'TrendingUp' : 'TrendingDown';
  };

  if (compact) {
    return (
      <div className="bg-card border border-border rounded-lg p-4 hover:shadow-card transition-smooth">
        <div className="flex items-center justify-between mb-2">
          {icon && (
            <div className="w-8 h-8 bg-accent/10 rounded-lg flex items-center justify-center">
              <Icon name={icon} size={16} className="text-accent" />
            </div>
          )}
          {trend && (
            <div className={`flex items-center gap-1 ${getTrendColor(trend)}`}>
              <Icon name={getTrendIcon(trend)} size={12} />
              <span className="text-xs font-body font-medium">{trend}</span>
            </div>
          )}
        </div>
        
        <div className="text-xl font-body font-bold text-foreground mb-1">
          {formatValue(value, format)}
        </div>
        
        <div className="text-xs text-muted-foreground font-body">
          {title}
        </div>
      </div>
    );
  }

  return (
    <div className="bg-card border border-border rounded-lg p-6 hover:shadow-card transition-smooth">
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-3">
          {icon && (
            <div className="w-10 h-10 bg-accent/10 rounded-lg flex items-center justify-center">
              <Icon name={icon} size={20} className="text-accent" />
            </div>
          )}
          <h3 className="font-body font-medium text-foreground">
            {title}
          </h3>
        </div>
        
        {trend && (
          <div className={`flex items-center gap-1 ${getTrendColor(trend)}`}>
            <Icon name={getTrendIcon(trend)} size={16} />
            <span className="text-sm font-body font-medium">{trend}</span>
          </div>
        )}
      </div>
      
      <div className="text-3xl font-body font-bold text-foreground mb-2">
        {formatValue(value, format)}
      </div>
      
      {description && (
        <p className="text-sm text-muted-foreground font-body">
          {description}
        </p>
      )}
    </div>
  );
};

export default MetricsCard;