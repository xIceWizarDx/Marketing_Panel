import React, { useState, useEffect } from 'react';
import GlobalHeader from '../../components/ui/GlobalHeader';
import NavigationDrawer from '../../components/ui/NavigationDrawer';
import BreadcrumbNavigation from '../../components/ui/BreadcrumbNavigation';
import { ToastProvider, useToast } from '../../components/ui/ToastNotificationSystem';
import Icon from '../../components/AppIcon';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';

// Import components
import MediaUploadArea from './components/MediaUploadArea';
import MediaGrid from './components/MediaGrid';
import MediaFilters from './components/MediaFilters';
import MediaDetailDrawer from './components/MediaDetailDrawer';
import BulkActions from './components/BulkActions';

const MediaLibraryContent = () => {
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [viewMode, setViewMode] = useState('grid');
  const [isFiltersOpen, setIsFiltersOpen] = useState(false);
  const [selectedItems, setSelectedItems] = useState([]);
  const [selectedItem, setSelectedItem] = useState(null);
  const [isDetailDrawerOpen, setIsDetailDrawerOpen] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [loading, setLoading] = useState(true);
  const [isMobile, setIsMobile] = useState(false);

  const { success, error } = useToast();

  const [filters, setFilters] = useState({
    fileTypes: [],
    dateRange: '',
    dimensions: [],
    tags: '',
    minSize: '',
    maxSize: ''
  });

  // Mock media data
  const [mediaItems, setMediaItems] = useState([
    {
      id: 1,
      name: 'promocao-verao-2025.jpg',
      type: 'image/jpeg',
      size: 2048576,
      dimensions: '1920x1080',
      url: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400',
      uploadedAt: '2025-01-20T10:30:00Z',
      tags: ['promoção', 'verão', 'marketing'],
      description: 'Imagem promocional para campanha de verão 2025'
    },
    {
      id: 2,
      name: 'produto-destaque.png',
      type: 'image/png',
      size: 1536000,
      dimensions: '1080x1080',
      url: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400',
      uploadedAt: '2025-01-19T14:15:00Z',
      tags: ['produto', 'destaque', 'quadrado'],
      description: 'Produto em destaque para redes sociais'
    },
    {
      id: 3,
      name: 'video-tutorial.mp4',
      type: 'video/mp4',
      size: 15728640,
      dimensions: '1920x1080',
      url: '/assets/video-placeholder.jpg',
      uploadedAt: '2025-01-18T09:45:00Z',
      tags: ['tutorial', 'vídeo', 'educativo'],
      description: 'Tutorial sobre uso da plataforma'
    },
    {
      id: 4,
      name: 'banner-instagram.jpg',
      type: 'image/jpeg',
      size: 1024000,
      dimensions: '1080x1920',
      url: 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=400',
      uploadedAt: '2025-01-17T16:20:00Z',
      tags: ['instagram', 'stories', 'banner'],
      description: 'Banner vertical para Instagram Stories'
    },
    {
      id: 5,
      name: 'logo-empresa.svg',
      type: 'image/svg+xml',
      size: 45056,
      dimensions: '512x512',
      url: 'https://images.unsplash.com/photo-1572044162444-ad60f128bdea?w=400',
      uploadedAt: '2025-01-16T11:10:00Z',
      tags: ['logo', 'marca', 'identidade'],
      description: 'Logo oficial da empresa'
    },
    {
      id: 6,
      name: 'campanha-natal.gif',
      type: 'image/gif',
      size: 3145728,
      dimensions: '800x600',
      url: 'https://images.unsplash.com/photo-1512389142860-9c449e58a543?w=400',
      uploadedAt: '2025-01-15T13:30:00Z',
      tags: ['natal', 'animação', 'campanha'],
      description: 'Animação para campanha de Natal'
    }
  ]);

  const [filteredItems, setFilteredItems] = useState(mediaItems);

  // Mock user data
  const user = {
    name: 'Maria Silva',
    email: 'maria@empresa.com'
  };

  // Check if mobile
  useEffect(() => {
    const checkMobile = () => {
      setIsMobile(window.innerWidth < 1024);
    };

    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  // Simulate loading
  useEffect(() => {
    const timer = setTimeout(() => {
      setLoading(false);
    }, 1000);

    return () => clearTimeout(timer);
  }, []);

  // Filter items based on search and filters
  useEffect(() => {
    let filtered = mediaItems;

    // Search filter
    if (searchQuery) {
      filtered = filtered?.filter(item =>
        item?.name?.toLowerCase()?.includes(searchQuery?.toLowerCase()) ||
        item?.tags?.some(tag => tag?.toLowerCase()?.includes(searchQuery?.toLowerCase()))
      );
    }

    // File type filter
    if (filters?.fileTypes?.length > 0) {
      filtered = filtered?.filter(item => {
        if (filters?.fileTypes?.includes('image') && item?.type?.startsWith('image/')) return true;
        if (filters?.fileTypes?.includes('video') && item?.type?.startsWith('video/')) return true;
        if (filters?.fileTypes?.includes('gif') && item?.type === 'image/gif') return true;
        return false;
      });
    }

    // Tags filter
    if (filters?.tags) {
      filtered = filtered?.filter(item =>
        item?.tags?.some(tag => tag?.toLowerCase()?.includes(filters?.tags?.toLowerCase()))
      );
    }

    setFilteredItems(filtered);
  }, [searchQuery, filters, mediaItems]);

  const handleUpload = async (files) => {
    setIsUploading(true);
    
    try {
      // Simulate upload process
      await new Promise(resolve => setTimeout(resolve, 2000));
      
      const newItems = files?.map((file, index) => ({
        id: Date.now() + index,
        name: file?.name,
        type: file?.type,
        size: file?.size,
        dimensions: '1920x1080', // Mock dimensions
        url: URL.createObjectURL(file),
        uploadedAt: new Date()?.toISOString(),
        tags: [],
        description: ''
      }));

      setMediaItems(prev => [...newItems, ...prev]);
      success(`${files?.length} arquivo(s) enviado(s) com sucesso!`);
    } catch (err) {
      error('Erro ao enviar arquivos. Tente novamente.');
    } finally {
      setIsUploading(false);
    }
  };

  const handleItemSelect = (itemId) => {
    setSelectedItems(prev => 
      prev?.includes(itemId)
        ? prev?.filter(id => id !== itemId)
        : [...prev, itemId]
    );
  };

  const handleItemClick = (item) => {
    setSelectedItem(item);
    setIsDetailDrawerOpen(true);
  };

  const handleItemUpdate = (updatedItem) => {
    setMediaItems(prev => 
      prev?.map(item => item?.id === updatedItem?.id ? updatedItem : item)
    );
    success('Mídia atualizada com sucesso!');
  };

  const handleItemDelete = (itemId) => {
    setMediaItems(prev => prev?.filter(item => item?.id !== itemId));
    setIsDetailDrawerOpen(false);
    setSelectedItem(null);
    success('Mídia excluída com sucesso!');
  };

  const handleBulkDelete = (itemIds) => {
    setMediaItems(prev => prev?.filter(item => !itemIds?.includes(item?.id)));
    setSelectedItems([]);
    success(`${itemIds?.length} item(s) excluído(s) com sucesso!`);
  };

  const handleBulkTag = (itemIds, tags) => {
    setMediaItems(prev => 
      prev?.map(item => 
        itemIds?.includes(item?.id)
          ? { ...item, tags: [...new Set([...(item.tags || []), ...tags])] }
          : item
      )
    );
    setSelectedItems([]);
    success('Tags adicionadas com sucesso!');
  };

  const handleBulkMove = (itemIds, folderId) => {
    // Mock move functionality
    console.log('Moving items', itemIds, 'to folder', folderId);
    setSelectedItems([]);
    success('Itens movidos com sucesso!');
  };

  const handleSelectAll = () => {
    if (selectedItems?.length === filteredItems?.length) {
      setSelectedItems([]);
    } else {
      setSelectedItems(filteredItems?.map(item => item?.id));
    }
  };

  return (
    <div className="min-h-screen bg-background">
      <GlobalHeader
        user={user}
        notificationCount={3}
        onDrawerToggle={() => setIsDrawerOpen(true)}
      />
      <NavigationDrawer
        isOpen={isDrawerOpen}
        onClose={() => setIsDrawerOpen(false)}
      />
      <main className="container mx-auto px-4 sm:px-6 lg:px-8 pt-16 py-6">
        <BreadcrumbNavigation
          customBreadcrumbs={[
            { label: 'Dashboard', path: '/dashboard', isHome: true },
            { label: 'Biblioteca de Mídia', path: '/media-library', isActive: true }
          ]}
        />

        <div className="flex flex-col lg:flex-row gap-6">
            {/* Desktop Filters Sidebar */}
            {!isMobile && (
              <MediaFilters
                isOpen={true}
                onClose={() => setIsFiltersOpen(false)}
                filters={filters}
                onFiltersChange={setFilters}
                isMobile={false}
              />
            )}

            {/* Main Content */}
            <div className="flex-1">
              {/* Header */}
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                  <h1 className="font-body font-bold text-2xl text-foreground">
                    Biblioteca de Mídia
                  </h1>
                  <p className="text-muted-foreground font-body text-sm mt-1">
                    Gerencie suas imagens, vídeos e outros arquivos
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  {/* Mobile Filter Button */}
                  {isMobile && (
                    <Button
                      variant="outline"
                      size="icon"
                      onClick={() => setIsFiltersOpen(true)}
                      aria-label="Abrir filtros"
                    >
                      <Icon name="Filter" size={20} />
                    </Button>
                  )}

                  {/* View Toggle */}
                  <div className="flex items-center bg-muted rounded-lg p-1">
                    <Button
                      variant={viewMode === 'grid' ? 'default' : 'ghost'}
                      size="icon"
                      onClick={() => setViewMode('grid')}
                      aria-label="Visualização em grade"
                    >
                      <Icon name="Grid3X3" size={16} />
                    </Button>
                    <Button
                      variant={viewMode === 'list' ? 'default' : 'ghost'}
                      size="icon"
                      onClick={() => setViewMode('list')}
                      aria-label="Visualização em lista"
                    >
                      <Icon name="List" size={16} />
                    </Button>
                  </div>
                </div>
              </div>

              {/* Search and Actions Bar */}
              <div className="flex flex-col sm:flex-row gap-4 mb-6">
                <div className="flex-1">
                  <Input
                    type="search"
                    placeholder="Buscar por nome ou tags..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e?.target?.value)}
                    className="w-full"
                  />
                </div>

                <div className="flex items-center gap-2">
                  {filteredItems?.length > 0 && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={handleSelectAll}
                      iconName={selectedItems?.length === filteredItems?.length ? 'CheckSquare' : 'Square'}
                      iconPosition="left"
                    >
                      {selectedItems?.length === filteredItems?.length ? 'Desmarcar' : 'Selecionar'} Todos
                    </Button>
                  )}

                  <span className="text-sm text-muted-foreground font-body">
                    {filteredItems?.length} {filteredItems?.length === 1 ? 'item' : 'itens'}
                  </span>
                </div>
              </div>

              {/* Upload Area */}
              <div className="mb-8">
                <MediaUploadArea
                  onUpload={handleUpload}
                  isUploading={isUploading}
                />
              </div>

              {/* Media Grid */}
              <MediaGrid
                items={filteredItems}
                viewMode={viewMode}
                selectedItems={selectedItems}
                onItemSelect={handleItemSelect}
                onItemClick={handleItemClick}
                loading={loading}
              />
            </div>
          </div>
      </main>
      {/* Mobile Filters */}
      {isMobile && (
        <MediaFilters
          isOpen={isFiltersOpen}
          onClose={() => setIsFiltersOpen(false)}
          filters={filters}
          onFiltersChange={setFilters}
          isMobile={true}
        />
      )}
      {/* Media Detail Drawer */}
      <MediaDetailDrawer
        item={selectedItem}
        isOpen={isDetailDrawerOpen}
        onClose={() => {
          setIsDetailDrawerOpen(false);
          setSelectedItem(null);
        }}
        onUpdate={handleItemUpdate}
        onDelete={handleItemDelete}
        isMobile={isMobile}
      />
      {/* Bulk Actions */}
      <BulkActions
        selectedItems={selectedItems}
        onClearSelection={() => setSelectedItems([])}
        onBulkDelete={handleBulkDelete}
        onBulkTag={handleBulkTag}
        onBulkMove={handleBulkMove}
      />
    </div>
  );
};

const MediaLibrary = () => {
  return (
    <ToastProvider>
      <MediaLibraryContent />
    </ToastProvider>
  );
};

export default MediaLibrary;