import React, { useState, useEffect } from 'react';

import GlobalHeader from '../../components/ui/GlobalHeader';
import NavigationDrawer from '../../components/ui/NavigationDrawer';
import BreadcrumbNavigation from '../../components/ui/BreadcrumbNavigation';
import { ToastProvider, useToast } from '../../components/ui/ToastNotificationSystem';
import Button from '../../components/ui/Button';
import Icon from '../../components/AppIcon';

// Import components
import StepProgress from './components/StepProgress';
import PlatformSelector from './components/PlatformSelector';
import MediaUploader from './components/MediaUploader';
import ContentComposer from './components/ContentComposer';
import SchedulingOptions from './components/SchedulingOptions';
import PostPreview from './components/PostPreview';

const ContentCreatorPage = () => {
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [currentStep, setCurrentStep] = useState(1);
  const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);
  const [isAutoSaving, setIsAutoSaving] = useState(false);
  const { success, error, warning } = useToast();

  // Form Data States
  const [selectedPlatforms, setSelectedPlatforms] = useState([]);
  const [uploadedMedia, setUploadedMedia] = useState([]);
  const [content, setContent] = useState({
    caption: '',
    hashtags: ''
  });
  const [schedulingData, setSchedulingData] = useState({
    publishType: 'now',
    scheduledDate: '',
    scheduledTime: '',
    timezone: 'America/Sao_Paulo',
    enableRepeat: false,
    repeatFrequency: 'none',
    repeatUntil: '',
    selectedPlatforms: []
  });

  // Mock user data
  const mockUser = {
    name: 'João Silva',
    email: 'joao@minhaempresa.com',
    avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face'
  };

  // Auto-save functionality
  useEffect(() => {
    const autoSaveInterval = setInterval(() => {
      if (hasUnsavedChanges) {
        setIsAutoSaving(true);
        
        // Simulate auto-save
        setTimeout(() => {
          setIsAutoSaving(false);
          setHasUnsavedChanges(false);
          success('Rascunho salvo automaticamente', { duration: 2000 });
        }, 1000);
      }
    }, 30000); // Auto-save every 30 seconds

    return () => clearInterval(autoSaveInterval);
  }, [hasUnsavedChanges, success]);

  // Update scheduling data when platforms change
  useEffect(() => {
    setSchedulingData(prev => ({
      ...prev,
      selectedPlatforms: selectedPlatforms
    }));
  }, [selectedPlatforms]);

  // Mark as unsaved when data changes
  useEffect(() => {
    setHasUnsavedChanges(true);
  }, [selectedPlatforms, uploadedMedia, content, schedulingData]);

  // Handle platform selection
  const handlePlatformToggle = (platformId) => {
    setSelectedPlatforms(prev => {
      if (prev?.includes(platformId)) {
        return prev?.filter(id => id !== platformId);
      } else {
        return [...prev, platformId];
      }
    });
  };

  // Handle media upload
  const handleMediaUpload = (mediaItem) => {
    setUploadedMedia(prev => [...prev, mediaItem]);
    success('Arquivo carregado com sucesso');
  };

  // Handle media removal
  const handleMediaRemove = (mediaId) => {
    setUploadedMedia(prev => prev?.filter(item => item?.id !== mediaId));
    warning('Arquivo removido');
  };

  // Handle content changes
  const handleContentChange = (field, value) => {
    setContent(prev => ({
      ...prev,
      [field]: value
    }));
  };

  // Handle scheduling changes
  const handleSchedulingChange = (newData) => {
    setSchedulingData(newData);
  };

  // Step navigation
  const handleStepClick = (stepId) => {
    if (stepId <= currentStep) {
      setCurrentStep(stepId);
    }
  };

  const handleNextStep = () => {
    if (currentStep < 5) {
      setCurrentStep(currentStep + 1);
    }
  };

  const handlePrevStep = () => {
    if (currentStep > 1) {
      setCurrentStep(currentStep - 1);
    }
  };

  // Validation for each step
  const canProceedToNextStep = () => {
    switch (currentStep) {
      case 1:
        return selectedPlatforms?.length > 0;
      case 2:
        return true; // Media is optional
      case 3:
        return content?.caption?.trim()?.length > 0 || uploadedMedia?.length > 0;
      case 4:
        return schedulingData?.publishType === 'now' || 
               (schedulingData?.scheduledDate && schedulingData?.scheduledTime);
      case 5:
        return true;
      default:
        return false;
    }
  };

  // Handle publish/schedule
  const handlePublish = () => {
    setIsAutoSaving(true);
    
    setTimeout(() => {
      setIsAutoSaving(false);
      success('Publicação realizada com sucesso!');
      
      // Reset form
      setSelectedPlatforms([]);
      setUploadedMedia([]);
      setContent({ caption: '', hashtags: '' });
      setSchedulingData({
        publishType: 'now',
        scheduledDate: '',
        scheduledTime: '',
        timezone: 'America/Sao_Paulo',
        enableRepeat: false,
        repeatFrequency: 'none',
        repeatUntil: '',
        selectedPlatforms: []
      });
      setCurrentStep(1);
      setHasUnsavedChanges(false);
    }, 2000);
  };

  const handleSchedule = () => {
    setIsAutoSaving(true);
    
    setTimeout(() => {
      setIsAutoSaving(false);
      success('Publicação agendada com sucesso!');
      
      // Reset form
      setSelectedPlatforms([]);
      setUploadedMedia([]);
      setContent({ caption: '', hashtags: '' });
      setSchedulingData({
        publishType: 'now',
        scheduledDate: '',
        scheduledTime: '',
        timezone: 'America/Sao_Paulo',
        enableRepeat: false,
        repeatFrequency: 'none',
        repeatUntil: '',
        selectedPlatforms: []
      });
      setCurrentStep(1);
      setHasUnsavedChanges(false);
    }, 2000);
  };

  // Handle exit with unsaved changes
  const handleExit = () => {
    if (hasUnsavedChanges) {
      const confirmExit = window.confirm(
        'Você tem alterações não salvas. Deseja sair mesmo assim?'
      );
      if (confirmExit) {
        window.location.href = '/dashboard';
      }
    } else {
      window.location.href = '/dashboard';
    }
  };

  // Render current step content
  const renderStepContent = () => {
    switch (currentStep) {
      case 1:
        return (
          <PlatformSelector
            selectedPlatforms={selectedPlatforms}
            onPlatformToggle={handlePlatformToggle}
            connectedPlatforms={[]}
          />
        );
      case 2:
        return (
          <MediaUploader
            uploadedMedia={uploadedMedia}
            onMediaUpload={handleMediaUpload}
            onMediaRemove={handleMediaRemove}
            selectedPlatforms={selectedPlatforms}
          />
        );
      case 3:
        return (
          <ContentComposer
            content={content}
            onContentChange={handleContentChange}
            selectedPlatforms={selectedPlatforms}
          />
        );
      case 4:
        return (
          <SchedulingOptions
            schedulingData={schedulingData}
            onSchedulingChange={handleSchedulingChange}
          />
        );
      case 5:
        return (
          <PostPreview
            selectedPlatforms={selectedPlatforms}
            uploadedMedia={uploadedMedia}
            content={content}
            schedulingData={schedulingData}
            onPublish={handlePublish}
            onSchedule={handleSchedule}
          />
        );
      default:
        return null;
    }
  };

  const customBreadcrumbs = [
    { label: 'Início', path: '/dashboard', isHome: true },
    { label: 'Criar Conteúdo', path: '/content-creator', isActive: true }
  ];

  return (
    <div className="min-h-screen bg-background">
      <GlobalHeader
        user={mockUser}
        notificationCount={3}
        onDrawerToggle={() => setIsDrawerOpen(true)}
      />
      <NavigationDrawer
        isOpen={isDrawerOpen}
        onClose={() => setIsDrawerOpen(false)}
      />
      <main className="pt-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <BreadcrumbNavigation customBreadcrumbs={customBreadcrumbs} />

          {/* Header with Actions */}
          <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
            <div>
              <h1 className="text-3xl font-body font-bold text-foreground mb-2">
                Criar Conteúdo
              </h1>
              <p className="text-muted-foreground font-body">
                Crie e agende publicações para suas redes sociais
              </p>
            </div>

          <div className="flex items-center gap-3 mt-4 lg:mt-0">
            {isAutoSaving && (
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Icon name="Loader2" size={16} className="animate-spin" />
                Salvando...
              </div>
            )}

            {hasUnsavedChanges && !isAutoSaving && (
              <div className="flex items-center gap-2 text-sm text-warning">
                <Icon name="AlertCircle" size={16} />
                Alterações não salvas
              </div>
            )}

            <Button
              variant="outline"
              onClick={handleExit}
              iconName="X"
              iconPosition="left"
            >
              Sair
            </Button>
          </div>
        </div>

        {/* Step Progress */}
        <div className="mb-8">
          <StepProgress
            currentStep={currentStep}
            onStepClick={handleStepClick}
          />
        </div>

        {/* Main Content */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Content Area */}
            <div className="lg:col-span-2">
              <div className="bg-card border border-border rounded-lg p-6">
                {renderStepContent()}
              </div>
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              {/* Quick Stats */}
              <div className="bg-card border border-border rounded-lg p-6">
                <h3 className="text-lg font-body font-bold text-foreground mb-4">
                  Resumo
                </h3>
                
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground font-body">
                      Plataformas
                    </span>
                    <span className="text-sm font-body font-medium text-foreground">
                      {selectedPlatforms?.length}
                    </span>
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground font-body">
                      Arquivos de mídia
                    </span>
                    <span className="text-sm font-body font-medium text-foreground">
                      {uploadedMedia?.length}
                    </span>
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground font-body">
                      Caracteres
                    </span>
                    <span className="text-sm font-body font-medium text-foreground">
                      {(content?.caption || '')?.length + (content?.hashtags || '')?.length}
                    </span>
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground font-body">
                      Status
                    </span>
                    <span className={`text-sm font-body font-medium ${
                      schedulingData?.publishType === 'now' ? 'text-success' : 'text-accent'
                    }`}>
                      {schedulingData?.publishType === 'now' ? 'Imediato' : 'Agendado'}
                    </span>
                  </div>
                </div>
              </div>

              {/* Tips */}
              <div className="bg-accent/5 border border-accent/20 rounded-lg p-6">
                <div className="flex items-start gap-3">
                  <Icon name="Lightbulb" size={20} className="text-accent flex-shrink-0 mt-0.5" />
                  <div>
                    <h4 className="text-sm font-body font-medium text-foreground mb-2">
                      Dica da Etapa
                    </h4>
                    <p className="text-xs text-muted-foreground font-body">
                      {currentStep === 1 && 'Selecione as plataformas onde deseja publicar seu conteúdo.'}
                      {currentStep === 2 && 'Adicione imagens ou vídeos para tornar sua publicação mais atrativa.'}
                      {currentStep === 3 && 'Escreva uma legenda envolvente e use hashtags relevantes.'}
                      {currentStep === 4 && 'Escolha o melhor horário para publicar baseado no seu público.'}
                      {currentStep === 5 && 'Revise tudo antes de publicar ou agendar sua postagem.'}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Navigation Buttons */}
          <div
            className="flex flex-col sm:flex-row justify-between items-center gap-4 mt-8 pt-6 border-t border-border"
          >
            <Button
              variant="outline"
              onClick={handlePrevStep}
              disabled={currentStep === 1}
              iconName="ChevronLeft"
              iconPosition="left"
            >
              Voltar
            </Button>

            <div className="flex items-center gap-2 text-sm text-muted-foreground font-body">
              <Icon name="Save" size={16} />
              Auto-salvamento ativo
            </div>

            {currentStep < 5 ? (
              <Button
                variant="default"
                onClick={handleNextStep}
                disabled={!canProceedToNextStep()}
                iconName="ChevronRight"
                iconPosition="right"
              >
                Próximo
              </Button>
            ) : (
              <Button
                variant="success"
                onClick={schedulingData?.publishType === 'now' ? handlePublish : handleSchedule}
                loading={isAutoSaving}
                iconName={schedulingData?.publishType === 'now' ? 'Send' : 'Calendar'}
                iconPosition="left"
              >
                {schedulingData?.publishType === 'now' ? 'Publicar Agora' : 'Agendar Publicação'}
              </Button>
            )}
          </div>
        </div>
      </main>
    </div>
  );
};

// Wrapper component with ToastProvider
const ContentCreatorPageWithToast = () => {
  return (
    <ToastProvider>
      <ContentCreatorPage />
    </ToastProvider>
  );
};

export default ContentCreatorPageWithToast;
