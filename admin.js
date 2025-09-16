// Admin Panel JavaScript
let currentAdmin = null;
let adminPermissions = {};

// Initialize admin panel
document.addEventListener('DOMContentLoaded', function() {
    checkAdminSession();
    loadLicenses();
    initializeCategorySearch();
});

// Initialize category search functionality
function initializeCategorySearch() {
    // For add movie form
    setupCategorySearch('movieCategorySearch', 'movieCategory', 'categoryDropdown');

    // For edit movie form
    setupCategorySearch('editMovieCategorySearch', 'editMovieCategory', 'editCategoryDropdown');
}

// Setup category search for a specific form
function setupCategorySearch(searchInputId, hiddenInputId, dropdownId) {
    const searchInput = document.getElementById(searchInputId);
    const hiddenInput = document.getElementById(hiddenInputId);
    const dropdown = document.getElementById(dropdownId);

    if (!searchInput) return; // Element might not exist yet

    let allCategories = [];

    // Load categories for search
    fetch('admin_categories.php?action=get_categories')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allCategories = data.categories;
            }
        })
        .catch(error => {
            console.error('Load categories for search error:', error);
        });

    // Handle search input
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();

        if (searchTerm.length === 0) {
            dropdown.classList.remove('show');
            return;
        }

        const filteredCategories = allCategories.filter(category =>
            category.kategoria.toLowerCase().includes(searchTerm)
        );

        displayCategoryOptions(filteredCategories, dropdown, searchInput, hiddenInput);
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!searchInput.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
}

// Display category options in dropdown
function displayCategoryOptions(categories, dropdown, searchInput, hiddenInput) {
    if (categories.length === 0) {
        dropdown.innerHTML = '<div class="category-option">Brak wyników</div>';
        dropdown.classList.add('show');
        return;
    }

    let html = '';
    categories.forEach(category => {
        html += `<div class="category-option" data-id="${category.id}" data-name="${category.kategoria}">
                    ${category.kategoria}
                 </div>`;
    });

    dropdown.innerHTML = html;
    dropdown.classList.add('show');

    // Add click handlers to options
    dropdown.querySelectorAll('.category-option').forEach(option => {
        option.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-id');
            const categoryName = this.getAttribute('data-name');

            if (categoryId) {
                searchInput.value = categoryName;
                hiddenInput.value = categoryId;
                dropdown.classList.remove('show');
            }
        });
    });
}

// Check if admin is already logged in
function checkAdminSession() {
    fetch('admin_auth.php?action=check_session')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.admin) {
                currentAdmin = data.admin;
                adminPermissions = data.permissions;
                showAdminDashboard();
            }
        })
        .catch(error => {
            console.error('Session check error:', error);
        });
}

// Admin login form handler
document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('admin_auth.php?action=login', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentAdmin = data.admin;
            adminPermissions = data.permissions;
            showAdminDashboard();
            showAlert('loginAlert', 'Zalogowano pomyślnie!', 'success');
        } else {
            showAlert('loginAlert', data.message || 'Błąd logowania', 'error');
        }
    })
    .catch(error => {
        console.error('Login error:', error);
        showAlert('loginAlert', 'Błąd połączenia z serwerem', 'error');
    });
});

// Show admin dashboard
function showAdminDashboard() {
    document.getElementById('loginSection').style.display = 'none';
    document.getElementById('adminDashboard').style.display = 'block';
    document.getElementById('logoutContainer').style.display = 'block';

    updatePermissionsDisplay();
    loadMovies();
}

// Update permissions display
function updatePermissionsDisplay() {
    const permissionsDiv = document.getElementById('userPermissions');
    let html = `<strong>Użytkownik:</strong> ${currentAdmin.login} (${currentAdmin.stanowisko})<br>`;
    html += `<span class="permission-badge ${adminPermissions.dodawanie ? 'permission-enabled' : 'permission-disabled'}">
                ${adminPermissions.dodawanie ? '✓' : '✗'} Dodawanie
             </span>`;
    html += `<span class="permission-badge ${adminPermissions.edytowanie ? 'permission-enabled' : 'permission-disabled'}">
                ${adminPermissions.edytowanie ? '✓' : '✗'} Edytowanie
             </span>`;
    html += `<span class="permission-badge ${adminPermissions.usuwanie ? 'permission-enabled' : 'permission-disabled'}">
                ${adminPermissions.usuwanie ? '✓' : '✗'} Usuwanie
             </span>`;

    permissionsDiv.innerHTML = html;

    // Hide/show sections based on permissions
    const addButton = document.querySelector('[onclick="showSection(\'addMovieSection\')"]');
    if (!adminPermissions.dodawanie) {
        addButton.style.display = 'none';
    }
}

// Admin logout
function adminLogout() {
    fetch('admin_auth.php?action=logout')
        .then(response => response.json())
        .then(data => {
            currentAdmin = null;
            adminPermissions = {};
            document.getElementById('loginSection').style.display = 'block';
            document.getElementById('adminDashboard').style.display = 'none';
            document.getElementById('logoutContainer').style.display = 'none';
            document.getElementById('adminLoginForm').reset();
        })
        .catch(error => {
            console.error('Logout error:', error);
        });
}

// Show section navigation
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.admin-section').forEach(section => {
        section.classList.remove('active');
    });

    // Remove active class from nav buttons
    document.querySelectorAll('.admin-nav-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected section
    document.getElementById(sectionId).classList.add('active');

    // Add active class to clicked button
    event.target.classList.add('active');

    // Load data for specific sections
    if (sectionId === 'moviesSection') {
        loadMovies();
    } else if (sectionId === 'categoriesSection') {
        loadCategoriesList();
    }
}

// Load movies list
function loadMovies() {
    fetch('admin_movies.php?action=get_movies')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMovies(data.movies);
            } else {
                showAlert('moviesAlert', data.message || 'Błąd ładowania filmów', 'error');
            }
        })
        .catch(error => {
            console.error('Load movies error:', error);
            showAlert('moviesAlert', 'Błąd połączenia z serwerem', 'error');
        });
}

// Display movies in grid
function displayMovies(movies) {
    const moviesList = document.getElementById('moviesList');
    let html = '';

    movies.forEach(movie => {
        html += `
            <div class="movie-card">
                <h4>${movie.tytul}</h4>
                <p><strong>Rok:</strong> ${movie.rok_produkcji}</p>
                <p><strong>Ocena:</strong> ${movie.ocena_sr}/10</p>
                <p><strong>Kategoria:</strong> ${movie.kategoria_nazwa}</p>
                <p class="movie-description">${movie.opis.substring(0, 100)}...</p>
                <div class="movie-actions">
                    ${adminPermissions.edytowanie ? `<button class="btn-admin" onclick="editMovie(${movie.id})">Edytuj</button>` : ''}
                    ${adminPermissions.usuwanie ? `<button class="btn-admin btn-danger" onclick="deleteMovie(${movie.id}, '${movie.tytul}')">Usuń</button>` : ''}
                </div>
            </div>
        `;
    });

    moviesList.innerHTML = html || '<p>Brak filmów do wyświetlenia</p>';
}

// Search movies
function searchMovies() {
    const searchTerm = document.getElementById('movieSearch').value.toLowerCase();
    const movieCards = document.querySelectorAll('.movie-card');

    movieCards.forEach(card => {
        const title = card.querySelector('h4').textContent.toLowerCase();
        if (title.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Add movie form handler
document.getElementById('addMovieForm').addEventListener('submit', function(e) {
    e.preventDefault();

    if (!adminPermissions.dodawanie) {
        showAlert('addMovieAlert', 'Brak uprawnień do dodawania filmów', 'error');
        return;
    }

    const formData = new FormData(this);
    formData.append('id_uzytkownika', currentAdmin.id);

    fetch('admin_movies.php?action=add_movie', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('addMovieAlert', 'Film został dodany pomyślnie!', 'success');
            this.reset();
            loadMovies(); // Refresh movies list
        } else {
            showAlert('addMovieAlert', data.message || 'Błąd dodawania filmu', 'error');
        }
    })
    .catch(error => {
        console.error('Add movie error:', error);
        showAlert('addMovieAlert', 'Błąd połączenia z serwerem', 'error');
    });
});

// Edit movie
function editMovie(movieId) {
    if (!adminPermissions.edytowanie) {
        alert('Brak uprawnień do edytowania filmów');
        return;
    }

    fetch(`admin_movies.php?action=get_movie&id=${movieId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const movie = data.movie;
                document.getElementById('editMovieId').value = movie.id;
                document.getElementById('editMovieTitle').value = movie.tytul;
                document.getElementById('editMovieDescription').value = movie.opis;
                document.getElementById('editMovieYear').value = movie.rok_produkcji;

                // Set category search field
                if (movie.kategoria) {
                    fetch('admin_categories.php?action=get_categories')
                        .then(response => response.json())
                        .then(catData => {
                            if (catData.success) {
                                const category = catData.categories.find(cat => cat.id == movie.kategoria);
                                if (category) {
                                    document.getElementById('editMovieCategorySearch').value = category.kategoria;
                                    document.getElementById('editMovieCategory').value = category.id;
                                }
                            }
                        });
                }

                // Set other fields
                document.getElementById('editMovieThumbnail').value = movie.miniaturka_url || '';
                document.getElementById('editMovieQuality').value = movie.dostepne_jakosci || '';
                document.getElementById('editMovieLicense').value = movie.typ_licencji || '';

                document.getElementById('editMovieModal').style.display = 'block';
            } else {
                alert('Błąd ładowania danych filmu');
            }
        })
        .catch(error => {
            console.error('Edit movie error:', error);
            alert('Błąd połączenia z serwerem');
        });
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editMovieModal').style.display = 'none';
}

// Edit movie form handler
document.getElementById('editMovieForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const movieId = document.getElementById('editMovieId').value;
    formData.append('id', movieId);

    fetch('admin_movies.php?action=update_movie', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('editMovieAlert', 'Film został zaktualizowany!', 'success');
            closeEditModal();
            loadMovies(); // Refresh movies list
        } else {
            showAlert('editMovieAlert', data.message || 'Błąd aktualizacji filmu', 'error');
        }
    })
    .catch(error => {
        console.error('Update movie error:', error);
        showAlert('editMovieAlert', 'Błąd połączenia z serwerem', 'error');
    });
});

// Delete movie
function deleteMovie(movieId, movieTitle) {
    if (!adminPermissions.usuwanie) {
        alert('Brak uprawnień do usuwania filmów');
        return;
    }

    if (!confirm(`Czy na pewno chcesz usunąć film "${movieTitle}"?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('id', movieId);

    fetch('admin_movies.php?action=delete_movie', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('moviesAlert', 'Film został usunięty!', 'success');
            loadMovies(); // Refresh movies list
        } else {
            showAlert('moviesAlert', data.message || 'Błąd usuwania filmu', 'error');
        }
    })
    .catch(error => {
        console.error('Delete movie error:', error);
        showAlert('moviesAlert', 'Błąd połączenia z serwerem', 'error');
    });
}



// Load licenses for select
function loadLicenses() {
    fetch('admin_categories.php?action=get_licenses')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // For add movie form
                const addSelect = document.getElementById('movieLicense');
                if (addSelect) {
                    let html = '<option value="">Wybierz licencję</option>';
                    data.licenses.forEach(license => {
                        html += `<option value="${license.id}">${license.typ} (${license.cena} zł)</option>`;
                    });
                    addSelect.innerHTML = html;
                }

                // For edit movie form
                const editSelect = document.getElementById('editMovieLicense');
                if (editSelect) {
                    let html = '<option value="">Wybierz licencję</option>';
                    data.licenses.forEach(license => {
                        html += `<option value="${license.id}">${license.typ} (${license.cena} zł)</option>`;
                    });
                    editSelect.innerHTML = html;
                }
            }
        })
        .catch(error => {
            console.error('Load licenses error:', error);
        });
}

// Load categories list for management
function loadCategoriesList() {
    fetch('admin_categories.php?action=get_categories')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const categoriesList = document.getElementById('categoriesList');
                let html = '<div class="movies-grid">';
                data.categories.forEach(category => {
                    html += `
                        <div class="movie-card">
                            <h4>${category.kategoria}</h4>
                            <div class="movie-actions">
                                <button class="btn-admin btn-danger" onclick="deleteCategory(${category.id}, '${category.kategoria}')">Usuń</button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                categoriesList.innerHTML = html;
            }
        })
        .catch(error => {
            console.error('Load categories list error:', error);
        });
}

// Add category
function addCategory() {
    const categoryName = document.getElementById('newCategory').value.trim();
    if (!categoryName) {
        showAlert('categoriesAlert', 'Podaj nazwę kategorii', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('kategoria', categoryName);

    fetch('admin_categories.php?action=add_category', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('categoriesAlert', 'Kategoria została dodana!', 'success');
            document.getElementById('newCategory').value = '';
            loadCategoriesList();
        } else {
            showAlert('categoriesAlert', data.message || 'Błąd dodawania kategorii', 'error');
        }
    })
    .catch(error => {
        console.error('Add category error:', error);
        showAlert('categoriesAlert', 'Błąd połączenia z serwerem', 'error');
    });
}

// Delete category
function deleteCategory(categoryId, categoryName) {
    if (!confirm(`Czy na pewno chcesz usunąć kategorię "${categoryName}"?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('id', categoryId);

    fetch('admin_categories.php?action=delete_category', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('categoriesAlert', 'Kategoria została usunięta!', 'success');
            loadCategoriesList();
        } else {
            showAlert('categoriesAlert', data.message || 'Błąd usuwania kategorii', 'error');
        }
    })
    .catch(error => {
        console.error('Delete category error:', error);
        showAlert('categoriesAlert', 'Błąd połączenia z serwerem', 'error');
    });
}

// Show alert messages
function showAlert(containerId, message, type) {
    const container = document.getElementById(containerId);
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    container.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;

    // Auto-hide after 5 seconds
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('editMovieModal');
    if (event.target === modal) {
        closeEditModal();
    }
});
