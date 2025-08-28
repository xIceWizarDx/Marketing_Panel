import React from 'react';
import Icon from '../../../components/AppIcon';

const StepProgress = ({ currentStep, onStepClick }) => {
  const steps = [
    {
      id: 1,
      title: 'Plataformas',
      description: 'Selecionar onde publicar',
      icon: 'CheckSquare'
    },
    {
      id: 2,
      title: 'Mídia',
      description: 'Adicionar imagens/vídeos',
      icon: 'Upload'
    },
    {
      id: 3,
      title: 'Conteúdo',
      description: 'Escrever legenda',
      icon: 'Type'
    },
    {
      id: 4,
      title: 'Agendamento',
      description: 'Definir quando publicar',
      icon: 'Clock'
    },
    {
      id: 5,
      title: 'Visualizar',
      description: 'Revisar e publicar',
      icon: 'Eye'
    }
  ];

  const getStepStatus = (stepId) => {
    if (stepId < currentStep) return 'completed';
    if (stepId === currentStep) return 'active';
    return 'pending';
  };

  const getStepStyles = (status) => {
    switch (status) {
      case 'completed':
        return 'bg-success text-success-foreground border-success';
      case 'active':
        return 'bg-accent text-accent-foreground border-accent';
      case 'pending':
      default:
        return 'bg-muted text-muted-foreground border-border';
    }
  };

  return (
    <div className="w-full">
      {/* Mobile Progress Bar */}
      <div className="lg:hidden mb-6">
        <div className="flex items-center justify-between mb-2">
          <span className="text-sm font-body font-medium text-foreground">
            Etapa {currentStep} de {steps?.length}
          </span>
          <span className="text-sm text-muted-foreground font-body">
            {Math.round((currentStep / steps?.length) * 100)}%
          </span>
        </div>
        
        <div className="w-full bg-muted rounded-full h-2">
          <div
            className="bg-accent h-2 rounded-full transition-smooth"
            style={{ width: `${(currentStep / steps?.length) * 100}%` }}
          />
        </div>
        
        <div className="mt-3 text-center">
          <h3 className="text-lg font-body font-bold text-foreground">
            {steps?.[currentStep - 1]?.title}
          </h3>
          <p className="text-sm text-muted-foreground font-body">
            {steps?.[currentStep - 1]?.description}
          </p>
        </div>
      </div>
      {/* Desktop Step Navigation */}
      <div className="hidden lg:block">
        <nav aria-label="Progresso da criação">
          <ol className="flex items-center justify-between">
            {steps?.map((step, index) => {
              const status = getStepStatus(step?.id);
              const isClickable = step?.id <= currentStep;
              
              return (
                <li key={step?.id} className="flex-1">
                  <div className="flex items-center">
                    {/* Step Circle */}
                    <button
                      onClick={() => isClickable && onStepClick(step?.id)}
                      disabled={!isClickable}
                      className={`relative flex items-center justify-center w-10 h-10 rounded-full border-2 transition-smooth ${
                        getStepStyles(status)
                      } ${isClickable ? 'cursor-pointer hover:scale-105' : 'cursor-not-allowed'}`}
                      aria-current={status === 'active' ? 'step' : undefined}
                    >
                      {status === 'completed' ? (
                        <Icon name="Check" size={20} />
                      ) : (
                        <Icon name={step?.icon} size={20} />
                      )}
                    </button>

                    {/* Step Content */}
                    <div className="ml-4 min-w-0 flex-1">
                      <p className={`text-sm font-body font-medium ${
                        status === 'active' ? 'text-accent' : 
                        status === 'completed'? 'text-success' : 'text-muted-foreground'
                      }`}>
                        {step?.title}
                      </p>
                      <p className="text-xs text-muted-foreground font-body">
                        {step?.description}
                      </p>
                    </div>

                    {/* Connector Line */}
                    {index < steps?.length - 1 && (
                      <div className="flex-1 ml-4">
                        <div className={`h-0.5 transition-smooth ${
                          step?.id < currentStep ? 'bg-success' : 'bg-border'
                        }`} />
                      </div>
                    )}
                  </div>
                </li>
              );
            })}
          </ol>
        </nav>
      </div>
    </div>
  );
};

export default StepProgress;