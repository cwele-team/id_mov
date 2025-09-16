// Movies page functionality
document.addEventListener('DOMContentLoaded', () => {
    const moviesGrid = document.querySelector('.movies-grid');
    const genreFilter = document.querySelector('.genre-filter');
    const sortSelect = document.querySelector('.sort-select');

    let currentGenre = null;
    let currentSort = 'popularity';

    // Function to fetch and display categories
    async function fetchCategoriesAndPopulateFilter() {
        try {
            const response = await fetch('categories_api.php');
            const data = await response.json();
            if (data.success) {
                const categories = data.categories;
                genreFilter.innerHTML = `
                    <button class="genre-tag active" data-genre="all">Wszystkie</button>
                    ${categories.map(category => `
                        <button class="genre-tag" data-genre="${category.id}">${category.name}</button>
                    `).join('')}
                `;
            } else {
                console.error('Failed to fetch categories:', data.error);
            }
        } catch (error) {
            console.error('Error fetching categories:', error);
        }
    }

    // Function to fetch and display movies
    async function fetchMovies() {
        let url = 'movies_api.php';
        const params = new URLSearchParams();

        if (currentGenre && currentGenre !== 'all') {
            params.append('categoryId', currentGenre);
        }

        // Add sorting parameter if needed (currently sorting happens on frontend)
        // if (currentSort && currentSort !== 'popularity') {
        //     params.append('sort', currentSort);
        // }

        if (params.toString()) {
            url += '?' + params.toString();
        }

        try {
            const response = await fetch(url);
            const data = await response.json();
            if (data.success) {
                let movies = data.movies;
                // Apply sorting (backend sort not implemented yet)
                movies.sort((a, b) => {
                    switch (currentSort) {
                        case 'rating':
                            return parseFloat(b.rating) - parseFloat(a.rating);
                        case 'title':
                            return a.title.localeCompare(b.title);
                        case 'newest':
                            return parseInt(b.year) - parseInt(a.year);
                        case 'oldest':
                            return parseInt(a.year) - parseInt(b.year);
                        default:
                            return parseFloat(b.rating) - parseFloat(a.rating);
                    }
                });
                moviesGrid.innerHTML = movies.map(movie => `
                    <div class="movie-card" data-movie-id="${movie.id}">
                        <img src="${movie.imageUrl}" alt="${movie.title}">
                        <div class="movie-card-content">
                            <h3 class="movie-card-title">${movie.title}</h3>
                            <div class="movie-card-meta">
                                <span>${movie.year}</span>
                                <span>•</span>
                                <span>${movie.rating}</span>
                            </div>
                        </div>
                    </div>
                `).join('');

                // Add event listeners for movie cards
                document.querySelectorAll('.movie-card').forEach(card => {
                    card.addEventListener('click', () => {
                        const movieId = card.dataset.movieId;
                        window.location.href = `Filmy.php?id=${movieId}`;
                    });
                });

            } else {
                console.error('Failed to fetch movies:', data.error);
            }
        } catch (error) {
            console.error('Error fetching movies:', error);
        }
    }

    // Event listeners
    genreFilter.addEventListener('click', (e) => {
        if (e.target.classList.contains('genre-tag')) {
            document.querySelectorAll('.genre-tag').forEach(tag =>
                tag.classList.remove('active'));
            e.target.classList.add('active');
            currentGenre = e.target.dataset.genre;
            fetchMovies(); // Call fetchMovies instead of updateMovies
        }
    });

    sortSelect.addEventListener('change', (e) => {
        currentSort = e.target.value;
        fetchMovies(); // Call fetchMovies instead of updateMovies
    });

    // Initial render
    fetchCategoriesAndPopulateFilter();
    fetchMovies();
});
