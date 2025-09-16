// Global movies array - will be populated from database
let movies = [];
let moviesLoaded = false;
let categories = [];
let categoriesLoaded = false;

// Function to load movies from database
async function loadMoviesFromDatabase() {
    if (moviesLoaded) {
        return movies;
    }

    try {
        console.log('🎬 Loading movies from database...');
        const response = await fetch('movies_api.php');

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.movies) {
            movies = data.movies;
            moviesLoaded = true;

            // Make movies globally available
            window.movies = movies;

            console.log(`✅ Loaded ${movies.length} movies from database`);
            return movies;
        } else {
            throw new Error(data.error || 'Failed to load movies');
        }
    } catch (error) {
        console.error('❌ Error loading movies from database:', error);

        // Fallback to empty array if database fails
        movies = [];
        window.movies = movies;

        // Show user-friendly error message
        showErrorMessage('Nie udało się załadować filmów z bazy danych. Spróbuj odświeżyć stronę.');

        return movies;
    }
}

// Function to show error messages to users
function showErrorMessage(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-notification';
    errorDiv.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: #dc2626;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        z-index: 1000;
        max-width: 300px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    `;
    errorDiv.textContent = message;
    document.body.appendChild(errorDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

// Function to ensure movies are loaded before using them
async function ensureMoviesLoaded() {
    if (!moviesLoaded) {
        await loadMoviesFromDatabase();
    }
    return movies;
}

// Function to load categories from database
async function loadCategoriesFromDatabase() {
    if (categoriesLoaded) {
        return categories;
    }

    try {
        console.log('📂 Loading categories from database...');
        const response = await fetch('categories_api.php');

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.categories) {
            categories = data.categories;
            categoriesLoaded = true;

            // Make categories globally available
            window.categories = categories;

            console.log(`✅ Loaded ${categories.length} categories from database`);
            return categories;
        } else {
            throw new Error(data.error || 'Failed to load categories');
        }
    } catch (error) {
        console.error('❌ Error loading categories from database:', error);

        // Fallback to empty array if database fails
        categories = [];
        window.categories = categories;

        // Show user-friendly error message
        showErrorMessage('Nie udało się załadować kategorii z bazy danych. Spróbuj odświeżyć stronę.');

        return categories;
    }
}

// Function to ensure categories are loaded before using them
async function ensureCategoriesLoaded() {
    if (!categoriesLoaded) {
        await loadCategoriesFromDatabase();
    }
    return categories;
}

    // Initialize Lucide icons
    document.addEventListener('DOMContentLoaded', () => {
      lucide.createIcons();
    });

    // Watchlist functions
    async function getWatchlist() {
      const currentUser = session.get();
      if (!currentUser) return [];

      try {
        const response = await fetch('watchlist.php?action=get', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json'
          }
        });

        if (!response.ok) throw new Error('Failed to fetch watchlist');
        const data = await response.json();

        // Convert watchlist data to movie objects
        const watchlistMovies = data.watchlist.map(item => {
          return movies.find(movie => movie.title === item.movie_title);
        }).filter(movie => movie !== undefined);

        return watchlistMovies;
      } catch (error) {
        console.error('Error fetching watchlist:', error);
        return [];
      }
    }

    async function addToWatchlist(movieId) {
      const currentUser = session.get();
      if (!currentUser) {
        window.location.href = 'Logowanie.php?returnUrl=' + encodeURIComponent(window.location.pathname);
        return;
      }

      try {
        const response = await fetch('watchlist.php?action=add', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            movieTitle: movies[movieId].title
          })
        });

        if (!response.ok) throw new Error('Failed to add to watchlist');
        // await updateWatchlistUI(); // Update watchlist after adding
        return true;
      } catch (error) {
        console.error('Error adding to watchlist:', error);
        return false;
      }
    }

    async function removeFromWatchlist(movieId) {
      try {
        const response = await fetch('watchlist.php?action=remove', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            movieTitle: movies[movieId].title
          })
        });

        if (!response.ok) throw new Error('Failed to remove from watchlist');
        // await updateWatchlistUI(); // Update watchlist after removing
        return true;
      } catch (error) {
        console.error('Error removing from watchlist:', error);
        return false;
      }
    }

    async function isInWatchlist(movieId) {
      const watchlist = await getWatchlist();
      return watchlist.some(movie => movie.title === movies[movieId].title);
    }

    // Debug function to check watchlist status
    async function debugWatchlist() {
      console.log('=== WATCHLIST DEBUG ===');
      console.log('Current URL:', window.location.pathname);
      console.log('All cookies:', document.cookie);
      const watchlist = await getWatchlist();
      console.log('Watchlist:', watchlist);
      console.log('Watchlist container exists:', !!document.getElementById('watchlist'));
      console.log('======================');
    }

    // Make debug function available globally
    window.debugWatchlist = debugWatchlist;
    window.addToWatchlist = addToWatchlist;
    window.removeFromWatchlist = removeFromWatchlist;
    window.getWatchlist = getWatchlist;

    async function updateWatchlistUI() {
      const watchlist = await getWatchlist();
      const watchlistContainer = document.getElementById('watchlist');

      console.log('Updating watchlist UI:', watchlist);
      console.log('Watchlist container found:', !!watchlistContainer);

      if (watchlistContainer) {
        if (watchlist.length === 0) {
          watchlistContainer.innerHTML = '<div class="empty-watchlist">Twoja lista do obejrzenia jest pusta</div>';
        } else {
          watchlistContainer.innerHTML = watchlist.map((movie) => {
            const originalIndex = movies.findIndex(m => m.title === movie.title);
            return createMovieCard(movie, originalIndex);
          }).join('');
        }
      } else {
        console.log('Watchlist container not found on this page');
      }

      // Update featured movie watchlist button if it exists
      setTimeout(async () => {
        await updateFeaturedMovieButton();
      }, 10); // Small delay to ensure DOM has updated

      // Update any open movie overlay
      if (currentOverlay) {
        const movieId = parseInt(currentOverlay.querySelector('.movie-details-content').dataset.movieId);
        const watchlistButton = currentOverlay.querySelector('.watchlist-button');
        const inWatchlist = await isInWatchlist(movieId);

        if (watchlistButton) {
          watchlistButton.className = `btn ${inWatchlist ? 'btn-primary' : 'btn-secondary'} watchlist-button`;
          watchlistButton.innerHTML = `
            <i data-lucide="${inWatchlist ? 'check' : 'plus'}"></i>
            <span>${inWatchlist ? 'Usuń z listy' : 'Dodaj do listy'}</span>
          `;
          lucide.createIcons();
        }
      }

      // Re-setup movie overlay for new watchlist cards
      setupMovieOverlay();
    }

    // Create movie card HTML
    function createMovieCard(movie, index) {
      const primaryCategory = movie.categories[0];
      const watchlistButtonClass = 'btn-secondary'; // Default to add button state
      const watchlistText = 'Dodaj do listy';

      return `
        <div class="movie-card" data-movie-id="${index}" data-movie-db-id="${movie.id}" tabindex="0" role="button" aria-label="Otwórz szczegóły filmu ${movie.title}">
          <img src="${movie.imageUrl}" alt="Plakat filmu ${movie.title}">
          <div class="movie-card-overlay"></div>
          <span class="movie-category">${primaryCategory}</span>
          <span class="movie-duration">${movie.duration}</span>
          <div class="movie-card-content">
            <h3 class="movie-card-title">${movie.title}</h3>
            <div class="movie-card-meta">
              <span>${movie.year}</span>
              <span>•</span>
              <span>${movie.rating}</span>
            </div>
          </div>
        </div>
      `;
    }

    // Filter movies by category
    function getMoviesByCategory(category) {
      return movies.filter(movie => movie.categories.includes(category));
    }

    // Initialize movies page
    async function initializeMoviesPage() {
      await ensureMoviesLoaded();
      const moviesGrid = document.getElementById('movies-grid');
      if (!moviesGrid) return;

      // Check for search parameter in URL
      const urlParams = new URLSearchParams(window.location.search);
      const searchTerm = urlParams.get('search');

      let moviesToShow = movies;

      if (searchTerm) {
        // Filter movies based on search term
        moviesToShow = movies.filter(movie =>
          movie.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
          movie.genre.toLowerCase().includes(searchTerm.toLowerCase()) ||
          movie.categories.some(cat => cat.toLowerCase().includes(searchTerm.toLowerCase())) ||
          movie.description.toLowerCase().includes(searchTerm.toLowerCase())
        );

        // Update page header to show search results
        const moviesHeader = document.querySelector('.movies-header h2');
        if (moviesHeader) {
          moviesHeader.textContent = `Wyniki wyszukiwania dla: "${searchTerm}" (${moviesToShow.length})`;
        }
      }

      moviesGrid.innerHTML = moviesToShow.map((movie, index) => {
        const originalIndex = movies.findIndex(m => m.title === movie.title);
        return createMovieCard(movie, originalIndex);
      }).join('');

      setupMovieOverlay();
    }

    // Store the current featured movie index
    let currentFeaturedMovieIndex = null;

    // Update featured movie button
    async function updateFeaturedMovieButton() {
      if (currentFeaturedMovieIndex === null) return;

      // Look for the button by finding it properly - it might have different classes after update
      const featuredMovie = document.querySelector('.featured-movie');
      if (!featuredMovie) return;

      const featuredWatchlistButton = featuredMovie.querySelector('.action-buttons .btn:not(.btn-primary[href])');
      if (featuredWatchlistButton) {
        const inWatchlist = await isInWatchlist(currentFeaturedMovieIndex);

        // Update class and content
        featuredWatchlistButton.className = `btn ${inWatchlist ? 'btn-primary' : 'btn-secondary'}`;
        featuredWatchlistButton.setAttribute('aria-label', inWatchlist ? 'Usuń z listy do obejrzenia' : 'Dodaj do listy do obejrzenia');
        featuredWatchlistButton.innerHTML = `
          <i data-lucide="${inWatchlist ? 'check' : 'plus'}"></i>
          <span>${inWatchlist ? 'Usuń z listy' : 'Dodaj do listy'}</span>
        `;

        // Recreate icons
        lucide.createIcons();

        // Remove old event listeners and add new one
        const newButton = featuredWatchlistButton.cloneNode(true);
        featuredWatchlistButton.parentNode.replaceChild(newButton, featuredWatchlistButton);

        // Add new click handler
        newButton.onclick = async (e) => {
          e.preventDefault();
          console.log('Featured movie watchlist button clicked, current state:', await isInWatchlist(currentFeaturedMovieIndex));
          if (await isInWatchlist(currentFeaturedMovieIndex)) {
            if (await removeFromWatchlist(currentFeaturedMovieIndex)) {
              await updateFeaturedMovieButton();
              await updateWatchlistUI();
            }
          } else {
            if (await addToWatchlist(currentFeaturedMovieIndex)) {
              await updateFeaturedMovieButton();
              await updateWatchlistUI();
            }
          }
        };
      }
    }

    // Initialize featured movie
    async function initializeFeaturedMovie() {
      await ensureMoviesLoaded();
      const featuredMovie = document.querySelector('.featured-movie');
      if (!featuredMovie) return;

      if (movies.length === 0) {
        console.log('No movies available for featured section');
        return;
      }

      // Only set a new random movie if we don't have one yet
      if (currentFeaturedMovieIndex === null) {
        currentFeaturedMovieIndex = Math.floor(Math.random() * movies.length);
      }

      const randomMovie = movies[currentFeaturedMovieIndex];
      const inWatchlist = await isInWatchlist(currentFeaturedMovieIndex);

      featuredMovie.querySelector('.featured-backdrop').style.backgroundImage = `url(${randomMovie.imageUrl})`;
      featuredMovie.querySelector('.featured-backdrop').setAttribute('aria-hidden', 'true');
      featuredMovie.querySelector('.featured-title').textContent = randomMovie.title;
      featuredMovie.querySelector('.rating').textContent = randomMovie.rating;
      featuredMovie.querySelector('.year').textContent = randomMovie.year;
      featuredMovie.querySelector('.duration').textContent = randomMovie.duration;
      featuredMovie.querySelector('.movie-description').textContent = randomMovie.description;

      // Update play button link
      const playButton = featuredMovie.querySelector('.btn-primary');
      if (playButton) {
        playButton.href = `player.php?id=${currentFeaturedMovieIndex}`;
        playButton.setAttribute('aria-label', `Odtwórz film ${randomMovie.title}`);
        console.log('🎬 Featured movie play button link:', playButton.href);
      }

      // Update watchlist button - find the second button (not the play button)
      const watchlistButton = featuredMovie.querySelector('.action-buttons .btn:not([href])');
      if (watchlistButton) {
        // Remove any existing onclick handlers
        watchlistButton.onclick = null;

        watchlistButton.className = `btn ${inWatchlist ? 'btn-primary' : 'btn-secondary'}`;
        watchlistButton.setAttribute('aria-label', inWatchlist ? 'Usuń z listy do obejrzenia' : 'Dodaj do listy do obejrzenia');
        watchlistButton.innerHTML = `
          <i data-lucide="${inWatchlist ? 'check' : 'plus'}"></i>
          <span>${inWatchlist ? 'Usuń z listy' : 'Dodaj do listy'}</span>
        `;
        lucide.createIcons();

        // Add click handler
        watchlistButton.onclick = async (e) => {
          e.preventDefault();
          console.log('Featured movie button clicked - current watchlist state:', await isInWatchlist(currentFeaturedMovieIndex));
          if (await isInWatchlist(currentFeaturedMovieIndex)) {
            if (await removeFromWatchlist(currentFeaturedMovieIndex)) {
              await updateFeaturedMovieButton();
              await updateWatchlistUI();
            }
          } else {
            if (await addToWatchlist(currentFeaturedMovieIndex)) {
              await updateFeaturedMovieButton();
              await updateWatchlistUI();
            }
          }
        };
      }
    }

    // Initialize watchlist page
    async function initializeWatchlistPage() {
      await ensureMoviesLoaded();
      // Check if we're on the watchlist page
        if (window.location.pathname.includes('Lista_do_obejrzenia.php')) {
        console.log('Initializing watchlist page');
        debugWatchlist();

        // Force update watchlist UI
        setTimeout(async () => {
          await updateWatchlistUI();
        }, 100);
      }
    }

    // Initialize category page
    async function initializeCategoryPage() {
      await ensureMoviesLoaded();
      await ensureCategoriesLoaded();
      const categoryContainer = document.querySelector('.category-container');
      if (!categoryContainer) return;

      if (movies.length === 0) {
        categoryContainer.innerHTML = '<div class="error-message">Nie udało się załadować filmów.</div>';
        return;
      }

      // Get category from URL if present
      const urlParams = new URLSearchParams(window.location.search);
      const selectedCategory = urlParams.get('category');
      const searchQuery = urlParams.get('search');
      console.log('Selected category from URL:', selectedCategory);
      console.log('Search query from URL:', searchQuery);

      // Create category filter using database categories
      const categoryTagsContainer = document.getElementById('category-tags');
      if (categoryTagsContainer) {
        let categoryButtonsHTML = `<button class="category-tag${!selectedCategory ? ' active' : ''}" data-category="all" aria-pressed="${!selectedCategory}">Wszystkie</button>`;
        
        categories.forEach(category => {
          const isActive = selectedCategory === category.name;
          categoryButtonsHTML += `<button class="category-tag${isActive ? ' active' : ''}" data-category="${category.name}" aria-pressed="${isActive}">${category.name}</button>`;
        });
        
        categoryTagsContainer.innerHTML = categoryButtonsHTML;
      }

      const categoryTags = document.querySelectorAll('.category-tag');
      const movieGrid = document.querySelector('.movie-grid');

      // Initialize search functionality
      initializeSearchFunctionality();

      // Display initial movies based on selected category and search
      let initialMovies = movies;
      
      if (selectedCategory) {
        initialMovies = initialMovies.filter(movie => movie.categories && movie.categories.includes(selectedCategory));
      }
      
      if (searchQuery) {
        initialMovies = filterMoviesBySearch(initialMovies, searchQuery);
        document.getElementById('movie-search-input').value = searchQuery;
        showSearchResults(searchQuery, initialMovies.length);
      }

      console.log('Initial movies to display:', initialMovies.length);

      displayMovies(initialMovies);

      setupMovieOverlay();

      // Category filtering
      categoryTags.forEach(tag => {
        tag.addEventListener('click', () => {
          const selectedCategoryName = tag.dataset.category;
          console.log('Category clicked:', selectedCategoryName);
          
          // Update active state
          categoryTags.forEach(t => t.classList.remove('active'));
          tag.classList.add('active');
          categoryTags.forEach(t => {
            t.setAttribute('aria-pressed', 'false');
          });
          tag.setAttribute('aria-pressed', 'true');

          // Apply both category and search filters
          applyFilters(selectedCategoryName);
        });
      });
    }

    // Function to initialize search functionality
    function initializeSearchFunctionality() {
      const searchInput = document.getElementById('movie-search-input');
      const clearSearchBtn = document.getElementById('clear-search');
      const searchSuggestions = document.getElementById('search-suggestions');
      const resetSearchBtn = document.getElementById('reset-search');
      
      let searchTimeout;
      let currentSuggestionIndex = -1;

      // Search input handler with debouncing
      searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        
        // Show/hide clear button
        clearSearchBtn.style.display = query ? 'flex' : 'none';
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        if (query.length === 0) {
          hideSuggestions();
          applyFilters();
          hideSearchResults();
          return;
        }
        
        // Debounce search
        searchTimeout = setTimeout(() => {
          if (query.length >= 2) {
            showSuggestions(query);
            applyFilters(null, query);
          }
        }, 300);
      });

      // Clear search button
      clearSearchBtn.addEventListener('click', () => {
        searchInput.value = '';
        clearSearchBtn.style.display = 'none';
        hideSuggestions();
        applyFilters();
        hideSearchResults();
        searchInput.focus();
        updateURL();
      });

      // Reset search button
      if (resetSearchBtn) {
        resetSearchBtn.addEventListener('click', () => {
          searchInput.value = '';
          clearSearchBtn.style.display = 'none';
          hideSuggestions();
          hideSearchResults();
          
          // Reset category selection
          const categoryTags = document.querySelectorAll('.category-tag');
          categoryTags.forEach(tag => tag.classList.remove('active'));
          document.querySelector('.category-tag[data-category="all"]').classList.add('active');
          
          applyFilters();
          searchInput.focus();
          updateURL();
        });
      }

      // Keyboard navigation for suggestions
      searchInput.addEventListener('keydown', (e) => {
        const suggestions = searchSuggestions.querySelectorAll('.search-suggestion');
        
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          currentSuggestionIndex = Math.min(currentSuggestionIndex + 1, suggestions.length - 1);
          updateSuggestionHighlight(suggestions);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          currentSuggestionIndex = Math.max(currentSuggestionIndex - 1, -1);
          updateSuggestionHighlight(suggestions);
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (currentSuggestionIndex >= 0 && suggestions[currentSuggestionIndex]) {
            selectSuggestion(suggestions[currentSuggestionIndex]);
          } else {
            hideSuggestions();
            applyFilters(null, searchInput.value.trim());
          }
        } else if (e.key === 'Escape') {
          hideSuggestions();
          currentSuggestionIndex = -1;
        }
      });

      // Hide suggestions when clicking outside
      document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
          hideSuggestions();
        }
      });
    }

    // Function to show search suggestions
    function showSuggestions(query) {
      const searchSuggestions = document.getElementById('search-suggestions');
      
      // Don't show suggestions for empty query
      if (!query || query.length < 2) {
        hideSuggestions();
        return;
      }
      
      const suggestions = generateSuggestions(query);
      
      if (suggestions.length === 0) {
        hideSuggestions();
        return;
      }
      
      // searchSuggestions.innerHTML = suggestions.map((suggestion, index) => `
      //   <div class="search-suggestion" data-type="${suggestion.type}" data-value="${suggestion.value}" role="option" tabindex="-1">
      //     <i data-lucide="${suggestion.icon}" class="suggestion-icon"></i>
      //     <div class="suggestion-content">
      //       <span class="suggestion-text">${highlightMatch(suggestion.text, query)}</span>
      //       <span class="suggestion-type">${suggestion.typeLabel}</span>
      //     </div>
      //   </div>
      // `).join('');
      
      searchSuggestions.style.display = 'block';
      lucide.createIcons();
      
      // Add click handlers to suggestions
      searchSuggestions.querySelectorAll('.search-suggestion').forEach(suggestion => {
        suggestion.addEventListener('click', () => selectSuggestion(suggestion));
      });
    }

    // Function to generate suggestions based on query
    function generateSuggestions(query) {
      const suggestions = [];
      const queryLower = query.toLowerCase();
      const maxSuggestions = 8;
      
      // Movie title suggestions
      const movieSuggestions = movies
        .filter(movie => movie.title.toLowerCase().includes(queryLower))
        .slice(0, 4)
        .map(movie => ({
          type: 'movie',
          value: movie.title,
          text: movie.title,
          typeLabel: 'Film',
          icon: 'film'
        }));
      
      // Category suggestions
      const categorySuggestions = categories
        .filter(category => category.name.toLowerCase().includes(queryLower))
        .slice(0, 3)
        .map(category => ({
          type: 'category',
          value: category.name,
          text: category.name,
          typeLabel: 'Kategoria',
          icon: 'tag'
        }));
      
      // Author suggestions
      const authorSuggestions = [...new Set(movies
        .filter(movie => movie.authors && movie.authors.toLowerCase().includes(queryLower))
        .map(movie => movie.authors))]
        .slice(0, 2)
        .map(author => ({
          type: 'author',
          value: author,
          text: author,
          typeLabel: 'Autor',
          icon: 'user'
        }));
      
      suggestions.push(...movieSuggestions, ...categorySuggestions, ...authorSuggestions);
      
      return suggestions.slice(0, maxSuggestions);
    }

    // Function to highlight matching text in suggestions
    function highlightMatch(text, query) {
      const regex = new RegExp(`(${query})`, 'gi');
      return text.replace(regex, '<mark>$1</mark>');
    }

    // Function to update suggestion highlight
    function updateSuggestionHighlight(suggestions) {
      suggestions.forEach((suggestion, index) => {
        suggestion.classList.toggle('highlighted', index === currentSuggestionIndex);
      });
    }

    // Function to select a suggestion
    function selectSuggestion(suggestionElement) {
      const type = suggestionElement.dataset.type;
      const value = suggestionElement.dataset.value;
      const searchInput = document.getElementById('movie-search-input');
      
      if (type === 'category') {
        // Select category and clear search
        const categoryTag = document.querySelector(`[data-category="${value}"]`);
        if (categoryTag) {
          categoryTag.click();
        }
        searchInput.value = '';
        document.getElementById('clear-search').style.display = 'none';
      } else {
        // Set search value and apply filter
        searchInput.value = value;
        document.getElementById('clear-search').style.display = 'flex';
        applyFilters(null, value);
      }
      
      hideSuggestions();
    }

    // Function to hide suggestions
    function hideSuggestions() {
      const searchSuggestions = document.getElementById('search-suggestions');
      searchSuggestions.style.display = 'none';
      currentSuggestionIndex = -1;
    }

    // Function to filter movies by search query
    function filterMoviesBySearch(moviesToFilter, query) {
      if (!query) return moviesToFilter;
      
      const queryLower = query.toLowerCase();
      return moviesToFilter.filter(movie => 
        movie.title.toLowerCase().includes(queryLower) ||
        movie.description.toLowerCase().includes(queryLower) ||
        movie.genre.toLowerCase().includes(queryLower) ||
        movie.categories.some(cat => cat.toLowerCase().includes(queryLower)) ||
        (movie.authors && movie.authors.toLowerCase().includes(queryLower)) ||
        (movie.tag && movie.tag.toLowerCase().includes(queryLower))
      );
    }

    // Function to apply both category and search filters
    function applyFilters(selectedCategory = null, searchQuery = null) {
      // Get current active category if not provided
      if (selectedCategory === null) {
        const activeCategory = document.querySelector('.category-tag.active');
        selectedCategory = activeCategory ? activeCategory.dataset.category : 'all';
      }
      
      // Get current search query if not provided
      if (searchQuery === null) {
        const searchInput = document.getElementById('movie-search-input');
        searchQuery = searchInput ? searchInput.value.trim() : '';
      }
      
      let filteredMovies = movies;
      
      // Apply category filter
      if (selectedCategory !== 'all') {
        filteredMovies = filteredMovies.filter(movie => 
          movie.categories && movie.categories.includes(selectedCategory)
        );
      }
      
      // Apply search filter
      if (searchQuery) {
        filteredMovies = filterMoviesBySearch(filteredMovies, searchQuery);
        showSearchResults(searchQuery, filteredMovies.length);
      } else {
        hideSearchResults();
      }
      
      console.log('Filtered movies:', filteredMovies.length);
      
      // Display results
      displayMovies(filteredMovies);
      
      // Update URL
      updateURL(selectedCategory, searchQuery);
    }

    // Function to display movies
    function displayMovies(moviesToDisplay) {
      const movieGrid = document.getElementById('movies-grid');
      const noResults = document.getElementById('no-results');
      
      if (moviesToDisplay.length === 0) {
        movieGrid.style.display = 'none';
        noResults.style.display = 'block';
      } else {
        movieGrid.style.display = 'grid';
        noResults.style.display = 'none';
        
        movieGrid.innerHTML = moviesToDisplay.map((movie) => {
          const originalIndex = movies.findIndex(m => m.title === movie.title);
          return createMovieCard(movie, originalIndex);
        }).join('');
      }
      
      setupMovieOverlay();
    }

    // Function to show search results info
    function showSearchResults(query, count) {
      const searchResultsInfo = document.getElementById('search-results-info');
      const resultsCount = document.getElementById('results-count');
      const searchTerm = document.getElementById('search-term');
      
      if (searchResultsInfo && resultsCount && searchTerm) {
        resultsCount.textContent = count;
        searchTerm.textContent = query;
        searchResultsInfo.style.display = 'block';
      }
    }

    // Function to hide search results info
    function hideSearchResults() {
      const searchResultsInfo = document.getElementById('search-results-info');
      if (searchResultsInfo) {
        searchResultsInfo.style.display = 'none';
      }
    }

    // Function to update URL with current filters
    function updateURL(category = null, search = null) {
      const url = new URL(window.location);
      
      // Get current values if not provided
      if (category === null) {
        const activeCategory = document.querySelector('.category-tag.active');
        category = activeCategory ? activeCategory.dataset.category : 'all';
      }
      
      if (search === null) {
        const searchInput = document.getElementById('movie-search-input');
        search = searchInput ? searchInput.value.trim() : '';
      }
      
      // Update URL parameters
      if (category && category !== 'all') {
        url.searchParams.set('category', category);
      } else {
        url.searchParams.delete('category');
      }
      
      if (search) {
        url.searchParams.set('search', search);
      } else {
        url.searchParams.delete('search');
      }
      
      // Update URL without page reload
      window.history.pushState({}, '', url);
    }


    // Populate movie rows with proper positioning of information
    document.addEventListener('DOMContentLoaded', async () => {
      // Load movies from database first
      await loadMoviesFromDatabase();

      // Load categories from database
      await loadCategoriesFromDatabase();

      // Initialize featured movie
      await initializeFeaturedMovie();

      const recommendedMovies = document.getElementById('recommended-movies');

      if (recommendedMovies) {
        if (movies.length > 0) {
          recommendedMovies.innerHTML = movies.map((movie, index) => createMovieCard(movie, index)).join('');
        } else {
          recommendedMovies.innerHTML = '<div class="empty-section">Brak dostępnych filmów</div>';
        }
      }

      // Dynamically populate category sections
      await populateCategorySections();

      // Set up movie overlay for all newly created cards on main page
      setupMovieOverlay();

      // Initialize movies page if we're on the movies page
      initializeMoviesPage();

      // Initialize category page if we're on the category page
      initializeCategoryPage();

      // Initialize watchlist page if we're on the watchlist page
      initializeWatchlistPage();

      // Initialize watchlist
      await updateWatchlistUI();

      // Set up movie overlay for any existing cards
      setupMovieOverlay();

      // Add click handlers for "Zobacz wszystkie" links
      const viewAllLinks = document.querySelectorAll('.view-all');
      viewAllLinks.forEach(link => {
        link.addEventListener('click', (e) => {
          const categoryHeader = e.target.closest('.category-header');
          if (categoryHeader) {
            const categoryTitle = categoryHeader.querySelector('h2').textContent;
            // Remove 'y' from the end if present (e.g., "Dramaty" -> "Dramat")
            const category = categoryTitle.replace(/y$/, '').trim();
            e.preventDefault();
            window.location.href = `Kategorie.php?category=${encodeURIComponent(category)}`;
          }
        });
      });
    });

    // Function to populate category sections dynamically
    async function populateCategorySections() {
      await ensureCategoriesLoaded();
      await ensureMoviesLoaded();

      const dynamicCategorySectionsContainer = document.getElementById('dynamic-category-sections');
      if (!dynamicCategorySectionsContainer) {
        console.warn('Container for dynamic category sections not found.');
        return;
      }

      dynamicCategorySectionsContainer.innerHTML = ''; // Clear existing content

      if (categories.length === 0) {
        dynamicCategorySectionsContainer.innerHTML = '<div class="empty-section">Brak dostępnych kategorii</div>';
        return;
      }

      // Limit to a maximum of 3 categories for dynamic sections
      const categoriesToDisplay = categories.slice(0, 3);

      categoriesToDisplay.forEach(category => {
        const categoryName = category.name;
        const categoryMovies = getMoviesByCategory(categoryName);

        if (categoryMovies.length > 0) {
          const categoryRow = document.createElement('div');
          categoryRow.className = 'category-row';
          categoryRow.dataset.category = categoryName; // Add data attribute for consistency
          categoryRow.setAttribute('role', 'region');
          categoryRow.setAttribute('aria-labelledby', `category-${categoryName.replace(/\s+/g, '-').toLowerCase()}-heading`);

          categoryRow.innerHTML = `
            <div class="category-header">
              <h2 id="category-${categoryName.replace(/\s+/g, '-').toLowerCase()}-heading">${categoryName}</h2>
              <a href="Kategorie.php?category=${encodeURIComponent(categoryName)}" class="view-all">Zobacz wszystkie</a>
            </div>
            <div class="movie-row" role="list" aria-label="Filmy z kategorii ${categoryName}">
              ${categoryMovies.map((movie, index) => {
                const originalIndex = movies.findIndex(m => m.title === movie.title);
                return createMovieCard(movie, originalIndex);
              }).join('')}
            </div>
          `;
          dynamicCategorySectionsContainer.appendChild(categoryRow);
        }
      });
    }

    // Global variable for overlay management
    let currentOverlay = null;

    // Movie details overlay
    async function updateOverlayButton(movieId, overlay) {
      const inWatchlist = await isInWatchlist(movieId);
      const watchlistButton = overlay.querySelector('.watchlist-button');

      if (watchlistButton) {
        watchlistButton.className = `btn ${inWatchlist ? 'btn-primary' : 'btn-secondary'} watchlist-button`;
        watchlistButton.setAttribute('aria-label', inWatchlist ? 'Usuń z listy do obejrzenia' : 'Dodaj do listy do obejrzenia');
        watchlistButton.innerHTML = `
          <i data-lucide="${inWatchlist ? 'check' : 'plus'}"></i>
          <span>${inWatchlist ? (inWatchlist ? 'Usuń z listy' : 'Dodaj do listy') : 'Dodaj do listy'}</span>
        `;
        lucide.createIcons();
      }
    }

    function setupMovieOverlay() {
      const movieCards = document.querySelectorAll('.movie-card:not([data-overlay-setup])');

      movieCards.forEach(card => {
        // Mark card as having overlay setup
        card.setAttribute('data-overlay-setup', 'true');

        const handleCardActivation = async () => {
          // Remove existing overlay if it exists
          if (currentOverlay) {
            currentOverlay.remove();
          }

          const overlay = document.createElement('div');
          overlay.className = 'movie-details-overlay';
          overlay.setAttribute('role', 'dialog');
          overlay.setAttribute('aria-modal', 'true');
          overlay.setAttribute('aria-labelledby', 'movie-details-title');

          const movieId = parseInt(card.dataset.movieId);
          const movieData = movies[movieId];
          const movieDbId = movieData.id; // Get the actual database ID
          const inWatchlist = await isInWatchlist(movieId);

          overlay.innerHTML = `
            <div class="movie-details-content" data-movie-id="${movieId}" data-movie-db-id="${movieDbId}">
              <button class="movie-details-close" aria-label="Zamknij szczegóły filmu">
                <i data-lucide="x"></i>
              </button>
              <div class="movie-details-header">
                <img src="${movieData.imageUrl}" alt="Plakat filmu ${movieData.title}" class="movie-details-poster">
                <div class="movie-details-info">
                  <h1 id="movie-details-title" class="movie-details-title">${movieData.title}</h1>
                  <div class="movie-details-meta">
                    <span class="movie-details-genre">${movieData.categories.join(', ')}</span>
                    <span class="movie-details-rating">
                      <i data-lucide="star" class="star-icon"></i>
                      <span>${movieData.rating}</span>
                    </span>
                    <span class="movie-details-authors">Autorzy: ${movieData.authors}</span>
                    ${movieData.tag ? `<span class="movie-details-tag">Tag: ${movieData.tag}</span>` : ''}
                  </div>
                  <p class="movie-details-description">${movieData.description}</p>
                  <div class="movie-details-actions">
                    <a href="player.php?id=${movieId}" class="btn btn-primary play-button" aria-label="Odtwórz film ${movieData.title}">
                      <i data-lucide="play"></i>
                      <span>Odtwórz film</span>
                    </a>
                    <button class="btn ${inWatchlist ? 'btn-primary' : 'btn-secondary'} watchlist-button" aria-label="${inWatchlist ? 'Usuń z listy do obejrzenia' : 'Dodaj do listy do obejrzenia'}">
                      <i data-lucide="${inWatchlist ? 'check' : 'plus'}"></i>
                      <span>${inWatchlist ? (inWatchlist ? 'Usuń z listy' : 'Dodaj do listy') : 'Dodaj do listy'}</span>
                    </button>
                     <button class="btn btn-secondary purchase-button" data-movie-db-id="${movieDbId}" aria-label="Wykup dostęp do filmu ${movieData.title}">
                       <i data-lucide="credit-card"></i>
                       <span>Wykup dostęp</span>
                     </button>
                     <a href="biuro_licencyjne.php" class="btn btn-secondary" aria-label="Zgłoś się po licencję do filmu ${movieData.title}">
                      <i data-lucide="briefcase"></i>
                      <span>Zgłoś się po licencję</span>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          `;

          document.body.appendChild(overlay);
          currentOverlay = overlay;
          lucide.createIcons();

          // Show overlay with animation
          requestAnimationFrame(() => {
            overlay.classList.add('active');
          });

          // Focus the close button for keyboard users
          const closeButton = overlay.querySelector('.movie-details-close');
          closeButton.focus();

          // Setup purchase button functionality
          const purchaseButton = overlay.querySelector('.purchase-button');
          if (purchaseButton) {
            purchaseButton.onclick = (e) => {
              e.preventDefault();
              e.stopPropagation();
              
              const dbMovieId = purchaseButton.getAttribute('data-movie-db-id');
              console.log('Purchase button clicked for database movie ID:', dbMovieId);
              
              // Create form and submit to startPaymentExample.php
              const form = document.createElement('form');
              form.action = 'startPaymentExample.php';
              form.method = 'POST';
              form.style.display = 'none';

              const movieIdInput = document.createElement('input');
              movieIdInput.type = 'hidden';
              movieIdInput.name = 'movieId';
              movieIdInput.value = dbMovieId;
              form.appendChild(movieIdInput);

              document.body.appendChild(form);
              form.submit();
            };
          }

          // Setup watchlist button functionality
          const watchlistButton = overlay.querySelector('.watchlist-button');
          if (watchlistButton) {
            watchlistButton.onclick = async (e) => {
              e.preventDefault();
              e.stopPropagation();
              console.log('Overlay watchlist button clicked for movie:', movieId);
              if (await isInWatchlist(movieId)) {
                if (await removeFromWatchlist(movieId)) {
                  await updateOverlayButton(movieId, overlay);
                  await updateWatchlistUI();
                }
              } else {
                if (await addToWatchlist(movieId)) {
                  await updateOverlayButton(movieId, overlay);
                  await updateWatchlistUI();
                }
              }
            };
          }

          // Close button functionality
          closeButton.addEventListener('click', (e) => {
            e.stopPropagation();
            overlay.classList.remove('active');
            setTimeout(() => {
              overlay.remove();
              currentOverlay = null;
              // Return focus to the movie card
              card.focus();
            }, 300);
          });

          // Close on escape key
          const handleEscape = (e) => {
            if (e.key === 'Escape' && overlay.classList.contains('active')) {
              overlay.classList.remove('active');
              setTimeout(() => {
                overlay.remove();
                currentOverlay = null;
                // Return focus to the movie card
                card.focus();
                document.removeEventListener('keydown', handleEscape);
              }, 300);
            }
          };
          document.addEventListener('keydown', handleEscape);

          // Close when clicking outside
          overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
              overlay.classList.remove('active');
              setTimeout(() => {
                overlay.remove();
                currentOverlay = null;
                // Return focus to the movie card
                card.focus();
              }, 300);
            }
          });
        };

        // Add both click and keyboard event listeners
        card.addEventListener('click', handleCardActivation);
        card.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            handleCardActivation();
          }
        });
      });
    }
