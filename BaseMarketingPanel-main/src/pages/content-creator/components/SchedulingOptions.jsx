import React, { useState } from 'react';
import Icon from '../../../components/AppIcon';
import Input from '../../../components/ui/Input';
import Button from '../../../components/ui/Button';
import Select from '../../../components/ui/Select';
import { Checkbox } from '../../../components/ui/Checkbox';

const SchedulingOptions = ({ schedulingData, onSchedulingChange }) => {
  const [showAdvanced, setShowAdvanced] = useState(false);

  const timezoneOptions = [
    { value: 'America/Sao_Paulo', label: 'Brasília (GMT-3)' },
    { value: 'America/New_York', label: 'Nova York (GMT-5)' },
    { value: 'Europe/London', label: 'Londres (GMT+0)' },
    { value: 'Asia/Tokyo', label: 'Tóquio (GMT+9)' }
  ];

  const repeatOptions = [
    { value: 'none', label: 'Não repetir' },
    { value: 'daily', label: 'Diariamente' },
    { value: 'weekly', label: 'Semanalmente' },
    { value: 'monthly', label: 'Mensalmente' }
  ];

  const optimalTimes = {
    instagram: [
      { time: '11:00', engagement: '85%', label: 'Pico de engajamento' },
      { time: '14:00', engagement: '78%', label: 'Boa interação' },
      { time: '17:00', engagement: '82%', label: 'Horário nobre' }
    ],
    facebook: [
      { time: '09:00', engagement: '76%', label: 'Início do dia' },
      { time: '13:00', engagement: '81%', label: 'Horário de almoço' },
      { time: '15:00', engagement: '79%', label: 'Tarde' }
    ],
    youtube: [
      { time: '14:00', engagement: '88%', label: 'Melhor horário' },
      { time: '18:00', engagement: '85%', label: 'Após trabalho' },
      { time: '20:00', engagement: '83%', label: 'Noite' }
    ],
    tiktok: [
      { time: '18:00', engagement: '92%', label: 'Pico absoluto' },
      { time: '19:00', engagement: '89%', label: 'Horário nobre' },
      { time: '21:00', engagement: '86%', label: 'Noite' }
    ],
    twitter: [
      { time: '08:00', engagement: '74%', label: 'Manhã' },
      { time: '12:00', engagement: '77%', label: 'Meio-dia' },
      { time: '17:00', engagement: '80%', label: 'Final do dia' }
    ]
  };

  const getCurrentDateTime = () => {
    const now = new Date();
    const year = now?.getFullYear();
    const month = String(now?.getMonth() + 1)?.padStart(2, '0');
    const day = String(now?.getDate())?.padStart(2, '0');
    const hours = String(now?.getHours())?.padStart(2, '0');
    const minutes = String(now?.getMinutes())?.padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
  };

  const getOptimalTimesForPlatforms = (platforms) => {
    if (!platforms || platforms?.length === 0) return [];
    
    const allTimes = [];
    platforms?.forEach(platform => {
      if (optimalTimes?.[platform]) {
        optimalTimes?.[platform]?.forEach(timeData => {
          allTimes?.push({
            ...timeData,
            platform: platform?.charAt(0)?.toUpperCase() + platform?.slice(1)
          });
        });
      }
    });
    
    return allTimes?.sort((a, b) => parseFloat(b?.engagement) - parseFloat(a?.engagement))?.slice(0, 6);
  };

  const handleDateTimeChange = (field, value) => {
    onSchedulingChange({
      ...schedulingData,
      [field]: value
    });
  };

  const setOptimalTime = (time) => {
    const today = new Date();
    const [hours, minutes] = time?.split(':');
    today?.setHours(parseInt(hours), parseInt(minutes), 0, 0);
    
    const year = today?.getFullYear();
    const month = String(today?.getMonth() + 1)?.padStart(2, '0');
    const day = String(today?.getDate())?.padStart(2, '0');
    const formattedTime = `${String(today?.getHours())?.padStart(2, '0')}:${String(today?.getMinutes())?.padStart(2, '0')}`;
    
    handleDateTimeChange('scheduledDate', `${year}-${month}-${day}`);
    handleDateTimeChange('scheduledTime', formattedTime);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-body font-bold text-foreground">
          Agendamento
        </h3>
        <div className="flex items-center gap-2">
          <Icon name="Clock" size={16} className="text-muted-foreground" />
          <span className="text-sm text-muted-foreground font-body">
            {schedulingData?.timezone || 'America/Sao_Paulo'}
          </span>
        </div>
      </div>
      {/* Publish Options */}
      <div className="space-y-4">
        <div className="flex flex-col sm:flex-row gap-4">
          <Button
            variant={schedulingData?.publishType === 'now' ? 'default' : 'outline'}
            onClick={() => handleDateTimeChange('publishType', 'now')}
            iconName="Send"
            iconPosition="left"
            className="flex-1"
          >
            Publicar Agora
          </Button>
          
          <Button
            variant={schedulingData?.publishType === 'schedule' ? 'default' : 'outline'}
            onClick={() => handleDateTimeChange('publishType', 'schedule')}
            iconName="Calendar"
            iconPosition="left"
            className="flex-1"
          >
            Agendar Publicação
          </Button>
        </div>

        {schedulingData?.publishType === 'schedule' && (
          <div className="space-y-4 p-4 bg-muted/30 rounded-lg">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Input
                label="Data"
                type="date"
                value={schedulingData?.scheduledDate || ''}
                onChange={(e) => handleDateTimeChange('scheduledDate', e?.target?.value)}
                min={new Date()?.toISOString()?.split('T')?.[0]}
              />
              
              <Input
                label="Horário"
                type="time"
                value={schedulingData?.scheduledTime || ''}
                onChange={(e) => handleDateTimeChange('scheduledTime', e?.target?.value)}
              />
            </div>

            <Select
              label="Fuso Horário"
              options={timezoneOptions}
              value={schedulingData?.timezone || 'America/Sao_Paulo'}
              onChange={(value) => handleDateTimeChange('timezone', value)}
            />
          </div>
        )}
      </div>
      {/* Optimal Times */}
      {schedulingData?.publishType === 'schedule' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h4 className="text-base font-body font-medium text-foreground">
              Horários Recomendados
            </h4>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setShowAdvanced(!showAdvanced)}
              iconName={showAdvanced ? 'ChevronUp' : 'ChevronDown'}
              iconPosition="right"
            >
              {showAdvanced ? 'Ocultar' : 'Ver Mais'}
            </Button>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {getOptimalTimesForPlatforms(schedulingData?.selectedPlatforms || [])?.slice(0, showAdvanced ? 6 : 3)?.map((timeData, index) => (
              <div
                key={index}
                className="bg-card border border-border rounded-lg p-3 cursor-pointer hover:border-accent transition-smooth"
                onClick={() => setOptimalTime(timeData?.time)}
              >
                <div className="flex items-center justify-between mb-2">
                  <span className="text-lg font-body font-bold text-foreground">
                    {timeData?.time}
                  </span>
                  <span className="text-sm font-body font-medium text-success">
                    {timeData?.engagement}
                  </span>
                </div>
                <p className="text-xs text-muted-foreground font-body mb-1">
                  {timeData?.label}
                </p>
                <p className="text-xs text-accent font-body">
                  {timeData?.platform}
                </p>
              </div>
            ))}
          </div>

          {getOptimalTimesForPlatforms(schedulingData?.selectedPlatforms || [])?.length === 0 && (
            <div className="text-center py-8">
              <Icon name="Clock" size={48} className="text-muted-foreground mx-auto mb-3" />
              <p className="text-sm text-muted-foreground font-body">
                Selecione plataformas para ver horários recomendados
              </p>
            </div>
          )}
        </div>
      )}
      {/* Advanced Options */}
      {schedulingData?.publishType === 'schedule' && (
        <div className="space-y-4">
          <div className="flex items-center gap-2">
            <Checkbox
              checked={schedulingData?.enableRepeat || false}
              onChange={(e) => handleDateTimeChange('enableRepeat', e?.target?.checked)}
            />
            <label className="text-sm font-body text-foreground">
              Repetir publicação
            </label>
          </div>

          {schedulingData?.enableRepeat && (
            <div className="pl-6 space-y-4">
              <Select
                label="Frequência"
                options={repeatOptions}
                value={schedulingData?.repeatFrequency || 'none'}
                onChange={(value) => handleDateTimeChange('repeatFrequency', value)}
              />

              {schedulingData?.repeatFrequency !== 'none' && (
                <Input
                  label="Repetir até"
                  type="date"
                  value={schedulingData?.repeatUntil || ''}
                  onChange={(e) => handleDateTimeChange('repeatUntil', e?.target?.value)}
                  min={schedulingData?.scheduledDate || new Date()?.toISOString()?.split('T')?.[0]}
                />
              )}
            </div>
          )}
        </div>
      )}
      {/* Scheduling Summary */}
      {schedulingData?.publishType === 'schedule' && schedulingData?.scheduledDate && schedulingData?.scheduledTime && (
        <div className="bg-accent/5 border border-accent/20 rounded-lg p-4">
          <div className="flex items-start gap-3">
            <Icon name="Calendar" size={20} className="text-accent flex-shrink-0 mt-0.5" />
            <div>
              <h4 className="text-sm font-body font-medium text-foreground mb-2">
                Resumo do Agendamento
              </h4>
              <div className="text-xs text-muted-foreground font-body space-y-1">
                <p>
                  <strong>Data:</strong> {new Date(schedulingData.scheduledDate)?.toLocaleDateString('pt-BR')}
                </p>
                <p>
                  <strong>Horário:</strong> {schedulingData?.scheduledTime}
                </p>
                <p>
                  <strong>Fuso:</strong> {schedulingData?.timezone || 'America/Sao_Paulo'}
                </p>
                {schedulingData?.enableRepeat && schedulingData?.repeatFrequency !== 'none' && (
                  <p>
                    <strong>Repetição:</strong> {repeatOptions?.find(opt => opt?.value === schedulingData?.repeatFrequency)?.label}
                  </p>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SchedulingOptions;