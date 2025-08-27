import React from 'react';
import { useLocation } from 'react-router-dom';
import Icon from '../AppIcon';

const BreadcrumbNavigation = ({ customBreadcrumbs = null }) => {
  const location = useLocation();

  const routeLabels = {
    '/dashboard': 'Dashboard',
    '/platform-connections': 'Conexões',
    '/content-creator': 'Criar Conteúdo',
    '/media-library': 'Biblioteca',
    '/content-calendar': 'Calendário',
    '/user-profile': 'Perfil'
  };

  const generateBreadcrumbs = () => {
    if (customBreadcrumbs) {
      return customBreadcrumbs;
    }

    const pathSegments = location?.pathname?.split('/')?.filter(Boolean);
    const breadcrumbs = [{ label: 'Início', path: '/dashboard', isHome: true }];

    let currentPath = '';
    pathSegments?.forEach((segment, index) => {
      currentPath += `/${segment}`;
      const label = routeLabels?.[currentPath] || segment?.charAt(0)?.toUpperCase() + segment?.slice(1);
      
      breadcrumbs?.push({
        label,
        path: currentPath,
        isActive: index === pathSegments?.length - 1
      });
    });

    return breadcrumbs;
  };

  const breadcrumbs = generateBreadcrumbs();

  // Don't show breadcrumbs on dashboard
  if (location?.pathname === '/dashboard' && !customBreadcrumbs) {
    return null;
  }

  return (
    <nav aria-label="Navegação estrutural" className="mb-6">
      <ol className="flex items-center gap-2 text-sm font-body">
        {breadcrumbs?.map((crumb, index) => (
          <li key={crumb?.path} className="flex items-center gap-2">
            {index > 0 && (
              <Icon 
                name="ChevronRight" 
                size={16} 
                className="text-muted-foreground flex-shrink-0" 
              />
            )}
            
            {crumb?.isActive ? (
              <span className="text-foreground font-medium flex items-center gap-1">
                {crumb?.isHome && <Icon name="Home" size={14} />}
                {crumb?.label}
              </span>
            ) : (
              <a
                href={crumb?.path}
                className="text-muted-foreground hover:text-foreground transition-smooth flex items-center gap-1"
              >
                {crumb?.isHome && <Icon name="Home" size={14} />}
                {crumb?.label}
              </a>
            )}
          </li>
        ))}
      </ol>
    </nav>
  );
};

export default BreadcrumbNavigation;