import React, { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import Icon from '../AppIcon';
import Button from './Button';

const NavigationDrawer = ({ isOpen, onClose }) => {
  const location = useLocation();

  const navigationItems = [
    { label: 'Dashboard', path: '/dashboard', icon: 'BarChart3', description: 'Visão geral e métricas' },
    { label: 'Criar Conteúdo', path: '/content-creator', icon: 'PenTool', description: 'Criar e editar posts' },
    { label: 'Calendário', path: '/content-calendar', icon: 'Calendar', description: 'Agendar publicações' },
    { label: 'Biblioteca', path: '/media-library', icon: 'FolderOpen', description: 'Gerenciar mídia' },
    { label: 'Conexões', path: '/platform-connections', icon: 'Link', description: 'Conectar plataformas' },
    { label: 'Perfil', path: '/user-profile', icon: 'User', description: 'Configurações da conta' }
  ];

  const isActive = (path) => location?.pathname === path;

  // Handle escape key
  useEffect(() => {
    const handleEscape = (e) => {
      if (e?.key === 'Escape' && isOpen) {
        onClose();
      }
    };

    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, [isOpen, onClose]);

  // Prevent body scroll when drawer is open
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'unset';
    }

    return () => {
      document.body.style.overflow = 'unset';
    };
  }, [isOpen]);

  // Focus trap
  useEffect(() => {
    if (isOpen) {
      const drawer = document.getElementById('navigation-drawer');
      const focusableElements = drawer?.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabIndex="-1"])'
      );
      const firstElement = focusableElements?.[0];
      const lastElement = focusableElements?.[focusableElements?.length - 1];

      const handleTabKey = (e) => {
        if (e?.key === 'Tab') {
          if (e?.shiftKey) {
            if (document.activeElement === firstElement) {
              lastElement?.focus();
              e?.preventDefault();
            }
          } else {
            if (document.activeElement === lastElement) {
              firstElement?.focus();
              e?.preventDefault();
            }
          }
        }
      };

      document.addEventListener('keydown', handleTabKey);
      firstElement?.focus();

      return () => document.removeEventListener('keydown', handleTabKey);
    }
  }, [isOpen]);

  if (!isOpen) return null;

  return (
    <>
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/50 z-40 lg:hidden transition-drawer"
        onClick={onClose}
        aria-hidden="true"
      />
      {/* Drawer */}
      <div
        id="navigation-drawer"
        className={`fixed left-0 top-0 h-full w-80 bg-card border-r border-border z-50 lg:hidden transition-drawer transform ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
        role="dialog"
        aria-modal="true"
        aria-label="Menu de navegação"
      >
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-border">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-accent rounded-lg flex items-center justify-center">
              <Icon name="Zap" size={24} color="white" />
            </div>
            <div>
              <h2 className="font-body font-bold text-lg text-primary">
                Marketing Panel
              </h2>
              <p className="text-xs text-muted-foreground font-body">
                Gestão de redes sociais
              </p>
            </div>
          </div>
          
          <Button
            variant="ghost"
            size="icon"
            onClick={onClose}
            aria-label="Fechar menu"
          >
            <Icon name="X" size={20} />
          </Button>
        </div>

        {/* Navigation */}
        <nav className="p-4">
          <ul className="space-y-2">
            {navigationItems?.map((item) => (
              <li key={item?.path}>
                <a
                  href={item?.path}
                  className={`flex items-center gap-4 p-3 rounded-lg transition-smooth group ${
                    isActive(item?.path)
                      ? 'bg-accent text-accent-foreground'
                      : 'text-card-foreground hover:bg-muted'
                  }`}
                  onClick={onClose}
                >
                  <div className={`flex-shrink-0 ${
                    isActive(item?.path) ? 'text-accent-foreground' : 'text-muted-foreground group-hover:text-foreground'
                  }`}>
                    <Icon name={item?.icon} size={20} />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="font-body font-medium text-sm">
                      {item?.label}
                    </div>
                    <div className={`text-xs font-body ${
                      isActive(item?.path) ? 'text-accent-foreground/80' : 'text-muted-foreground'
                    }`}>
                      {item?.description}
                    </div>
                  </div>
                  {isActive(item?.path) && (
                    <div className="w-2 h-2 bg-accent-foreground rounded-full flex-shrink-0" />
                  )}
                </a>
              </li>
            ))}
          </ul>
        </nav>

        {/* Footer */}
        <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-border bg-muted/30">
          <div className="text-center">
            <p className="text-xs text-muted-foreground font-body">
              © 2025 Marketing Panel
            </p>
            <p className="text-xs text-muted-foreground font-body mt-1">
              Versão 1.0.0
            </p>
          </div>
        </div>
      </div>
    </>
  );
};

export default NavigationDrawer;