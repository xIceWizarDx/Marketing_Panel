import React, { useState } from 'react';
import { useLocation } from 'react-router-dom';
import Icon from '../AppIcon';
import Button from './Button';

const GlobalHeader = ({ user, notificationCount = 0, onDrawerToggle }) => {
  const location = useLocation();
  const [isProfileMenuOpen, setIsProfileMenuOpen] = useState(false);

  const navigationItems = [
    { label: 'Dashboard', path: '/dashboard', icon: 'BarChart3' },
    { label: 'Criar Conteúdo', path: '/content-creator', icon: 'PenTool' },
    { label: 'Calendário', path: '/content-calendar', icon: 'Calendar' },
    { label: 'Biblioteca', path: '/media-library', icon: 'FolderOpen' },
    { label: 'Conexões', path: '/platform-connections', icon: 'Link' }
  ];

  const isActive = (path) => location?.pathname === path;

  return (
    <header className="fixed top-0 left-0 right-0 z-50 bg-card border-b border-border">
      <div className="flex items-center justify-between h-16 px-4 lg:px-6">
        {/* Logo and Mobile Menu */}
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="icon"
            className="lg:hidden"
            onClick={onDrawerToggle}
            aria-label="Abrir menu de navegação"
          >
            <Icon name="Menu" size={24} />
          </Button>
          
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 bg-accent rounded-lg flex items-center justify-center">
              <Icon name="Zap" size={20} color="white" />
            </div>
            <span className="font-body font-bold text-xl text-primary hidden sm:block">
              Marketing Panel
            </span>
          </div>
        </div>

        {/* Desktop Navigation */}
        <nav className="hidden lg:flex items-center gap-1">
          {navigationItems?.map((item) => (
            <a
              key={item?.path}
              href={item?.path}
              className={`flex items-center gap-2 px-4 py-2 rounded-md font-body font-medium text-sm transition-smooth hover:bg-muted ${
                isActive(item?.path)
                  ? 'bg-accent text-accent-foreground'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
            >
              <Icon name={item?.icon} size={18} />
              {item?.label}
            </a>
          ))}
        </nav>

        {/* Right Side Actions */}
        <div className="flex items-center gap-2">
          {/* Notifications */}
          <Button
            variant="ghost"
            size="icon"
            className="relative"
            aria-label={`Notificações${notificationCount > 0 ? ` (${notificationCount})` : ''}`}
          >
            <Icon name="Bell" size={20} />
            {notificationCount > 0 && (
              <span className="absolute -top-1 -right-1 bg-error text-error-foreground text-xs rounded-full w-5 h-5 flex items-center justify-center font-body font-medium">
                {notificationCount > 9 ? '9+' : notificationCount}
              </span>
            )}
          </Button>

          {/* Profile Menu */}
          <div className="relative">
            <Button
              variant="ghost"
              size="sm"
              className="flex items-center gap-2"
              onClick={() => setIsProfileMenuOpen(!isProfileMenuOpen)}
              aria-expanded={isProfileMenuOpen}
              aria-haspopup="true"
            >
              <div className="w-8 h-8 bg-muted rounded-full flex items-center justify-center">
                <Icon name="User" size={16} />
              </div>
              <span className="font-body font-medium text-sm hidden sm:block">
                {user?.name || 'Usuário'}
              </span>
              <Icon name="ChevronDown" size={16} />
            </Button>

            {isProfileMenuOpen && (
              <div className="absolute right-0 top-full mt-2 w-48 bg-popover border border-border rounded-lg shadow-modal py-2 z-50">
                <a
                  href="/user-profile"
                  className="flex items-center gap-3 px-4 py-2 text-sm font-body text-popover-foreground hover:bg-muted transition-smooth"
                >
                  <Icon name="Settings" size={16} />
                  Perfil
                </a>
                <hr className="my-2 border-border" />
                <button
                  className="flex items-center gap-3 px-4 py-2 text-sm font-body text-popover-foreground hover:bg-muted transition-smooth w-full text-left"
                  onClick={() => {
                    // Handle logout
                    console.log('Logout clicked');
                  }}
                >
                  <Icon name="LogOut" size={16} />
                  Sair
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
    </header>
  );
};

export default GlobalHeader;