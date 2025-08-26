import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import GlobalHeader from '../../components/ui/GlobalHeader';
import NavigationDrawer from '../../components/ui/NavigationDrawer';
import BreadcrumbNavigation from '../../components/ui/BreadcrumbNavigation';
import CalendarHeader from './components/CalendarHeader';
import CalendarGrid from './components/CalendarGrid';
import PostEditDrawer from './components/PostEditDrawer';
import CalendarSkeleton from './components/CalendarSkeleton';

const ContentCalendar = () => {
  const navigate = useNavigate();
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [currentDate, setCurrentDate] = useState(new Date());
  const [viewMode, setViewMode] = useState('month');
  const [selectedPost, setSelectedPost] = useState(null);
  const [isEditDrawerOpen, setIsEditDrawerOpen] = useState(false);
  const [posts, setPosts] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  // Mock user data
  const mockUser = {
    name: "Maria Silva",
    email: "maria.silva@email.com",
    avatar: "https://images.unsplash.com/photo-1494790108755-2616b612b786?w=150"
  };

  // Mock posts data
  const mockPosts = [
    {
      id: 1,
      platform: 'instagram',
      caption: `Começando a semana com energia positiva! ✨\n\nNada como um bom café da manhã para dar aquele boost matinal. Qual é o seu ritual favorito para começar o dia?`,
      hashtags: ['segunda', 'energia', 'cafe', 'motivacao', 'bomdia'],
      scheduledTime: new Date(2025, 0, 27, 9, 0)?.toISOString(),
      status: 'scheduled',
      media: [
        {
          url: 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400',
          type: 'image'
        }
      ],
      platforms: ['instagram']
    },
    {
      id: 2,
      platform: 'facebook',
      caption: `🎯 Dica de Marketing Digital:\n\nVocê sabia que posts com imagens recebem 2.3x mais engajamento que posts apenas com texto?\n\nInvista em conteúdo visual de qualidade!`,
      hashtags: ['marketing', 'dicas', 'engajamento', 'visual'],
      scheduledTime: new Date(2025, 0, 27, 14, 30)?.toISOString(),
      status: 'scheduled',
      media: [
        {
          url: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400',
          type: 'image'
        }
      ],
      platforms: ['facebook', 'instagram']
    },
    {
      id: 3,
      platform: 'youtube',
      caption: `🔥 NOVO VÍDEO NO AR!\n\n"Como criar conteúdo que converte em 2025"\n\nNeste vídeo eu compartilho as 5 estratégias que mais funcionam para criar conteúdo que realmente gera resultados.`,
      hashtags: ['youtube', 'conteudo', 'marketing', 'estrategia'],
      scheduledTime: new Date(2025, 0, 28, 10, 0)?.toISOString(),
      status: 'published',
      media: [
        {
          url: 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=400',
          type: 'image'
        }
      ],
      platforms: ['youtube']
    },
    {
      id: 4,
      platform: 'tiktok',
      caption: `POV: Você descobriu o segredo para criar conteúdo viral 🤫\n\n#marketingtips #viral #conteudo #dicas`,
      hashtags: ['marketingtips', 'viral', 'conteudo', 'dicas'],
      scheduledTime: new Date(2025, 0, 29, 16, 45)?.toISOString(),
      status: 'scheduled',
      media: [
        {
          url: 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400',
          type: 'video'
        }
      ],
      platforms: ['tiktok']
    },
    {
      id: 5,
      platform: 'twitter',
      caption: `Thread sobre as principais tendências de marketing digital para 2025 🧵\n\n1/ Personalização em massa será o novo padrão\n2/ IA generativa vai revolucionar a criação de conteúdo\n3/ Vídeos curtos continuam dominando`,
      hashtags: ['marketing2025', 'tendencias', 'thread'],
      scheduledTime: new Date(2025, 0, 30, 11, 15)?.toISOString(),
      status: 'draft',
      media: [],
      platforms: ['twitter']
    },
    {
      id: 6,
      platform: 'google-ads',
      caption: `Campanha: Curso de Marketing Digital\n\nSegmentação: Interessados em marketing, 25-45 anos\nOrçamento: R$ 500/dia\nObjetivo: Conversões`,
      hashtags: [],
      scheduledTime: new Date(2025, 0, 31, 8, 0)?.toISOString(),
      status: 'scheduled',
      media: [
        {
          url: 'https://images.unsplash.com/photo-1553028826-f4804a6dba3b?w=400',
          type: 'image'
        }
      ],
      platforms: ['google-ads']
    },
    {
      id: 7,
      platform: 'instagram',
      caption: `Sexta-feira chegou! 🎉\n\nQue tal relaxar um pouco e planejar o fim de semana? Às vezes precisamos desacelerar para voltar com mais energia na segunda.`,
      hashtags: ['sexta', 'fimdesemana', 'relaxar', 'equilibrio'],
      scheduledTime: new Date(2025, 0, 31, 17, 0)?.toISOString(),
      status: 'failed',
      media: [
        {
          url: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400',
          type: 'image'
        }
      ],
      platforms: ['instagram']
    }
  ];

  // Load posts data
  useEffect(() => {
    const loadPosts = () => {
      setIsLoading(true);
      // Simulate API call
      setTimeout(() => {
        setPosts(mockPosts);
        setIsLoading(false);
      }, 1500);
    };

    loadPosts();
  }, []);

  // Responsive view mode based on screen size
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth < 768) {
        setViewMode('day');
      } else if (window.innerWidth < 1024) {
        setViewMode('week');
      } else {
        setViewMode('month');
      }
    };

    handleResize();
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  const handlePostClick = (post) => {
    setSelectedPost(post);
    setIsEditDrawerOpen(true);
  };

  const handleEmptySlotClick = (date) => {
    // Navigate to content creator with pre-selected date
    navigate('/content-creator', { 
      state: { 
        scheduledDate: date?.toISOString()?.split('T')?.[0],
        scheduledTime: '09:00'
      }
    });
  };

  const handleNewPostClick = () => {
    navigate('/content-creator');
  };

  const handleTodayClick = () => {
    setCurrentDate(new Date());
  };

  const handlePostSave = (updatedPost) => {
    setPosts(prevPosts => 
      prevPosts?.map(post => 
        post?.id === updatedPost?.id ? updatedPost : post
      )
    );
  };

  const handlePostDelete = (postId) => {
    setPosts(prevPosts => 
      prevPosts?.filter(post => post?.id !== postId)
    );
  };

  return (
    <div className="min-h-screen bg-background">
      {/* Global Header */}
      <GlobalHeader
        user={mockUser}
        notificationCount={3}
        onDrawerToggle={() => setIsDrawerOpen(true)}
      />

      {/* Navigation Drawer */}
      <NavigationDrawer
        isOpen={isDrawerOpen}
        onClose={() => setIsDrawerOpen(false)}
      />

      {/* Main Content */}
      <main className="container mx-auto px-4 sm:px-6 lg:px-8 pt-16">
        {/* Breadcrumb */}
        <div className="py-4">
          <BreadcrumbNavigation />
        </div>

        {/* Calendar Header */}
        <CalendarHeader
          currentDate={currentDate}
          viewMode={viewMode}
          onViewModeChange={setViewMode}
          onDateChange={setCurrentDate}
          onTodayClick={handleTodayClick}
          onNewPostClick={handleNewPostClick}
        />

        {/* Calendar Content */}
        <div className="flex flex-col h-[calc(100vh-200px)]">
          {isLoading ? (
            <CalendarSkeleton viewMode={viewMode} />
          ) : (
            <CalendarGrid
              currentDate={currentDate}
              viewMode={viewMode}
              posts={posts}
              onPostClick={handlePostClick}
              onEmptySlotClick={handleEmptySlotClick}
            />
          )}
        </div>
      </main>

      {/* Post Edit Drawer */}
      <PostEditDrawer
        post={selectedPost}
        isOpen={isEditDrawerOpen}
        onClose={() => {
          setIsEditDrawerOpen(false);
          setSelectedPost(null);
        }}
        onSave={handlePostSave}
        onDelete={handlePostDelete}
      />
    </div>
  );
};

export default ContentCalendar;