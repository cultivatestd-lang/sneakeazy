import json
import pandas as pd
import numpy as np
from sklearn.decomposition import TruncatedSVD
import warnings
import os

# Suppress warnings for cleaner output
warnings.filterwarnings("ignore")

def load_data():
    """
    Memuat data dari file JSON dan mengubahnya menjadi DataFrame Pandas.
    Menggunakan path absolut relatif terhadap lokasi script ini.
    """
    print("Loading data...")
    try:
        # Tentukan base directory (folder dimana script ini berada)
        base_dir = os.path.dirname(os.path.abspath(__file__))
        data_dir = os.path.join(base_dir, 'data')

        # Load Interactions
        interactions_path = os.path.join(data_dir, 'interactions.json')
        with open(interactions_path, 'r') as f:
            interactions_data = json.load(f)
        df_interactions = pd.DataFrame(interactions_data)
        
        # Load Products
        products_path = os.path.join(data_dir, 'products.json')
        with open(products_path, 'r') as f:
            products_data = json.load(f)
        df_products = pd.DataFrame(products_data)
        
        # Pastikan tipe data sesuai
        df_interactions['user_id'] = df_interactions['user_id'].astype(str)
        df_interactions['product_id'] = df_interactions['product_id'].astype(str)
        df_interactions['rating'] = df_interactions['rating'].astype(float)
        
        df_products['id'] = df_products['id'].astype(str)
        
        # Merge untuk mendapatkan nama produk di tabel interaksi
        df_merged = pd.merge(df_interactions, df_products[['id', 'product_name']], left_on='product_id', right_on='id', how='left')
        
        print(f"Data Loaded: {len(df_merged)} interactions, {len(df_products)} products.")
        return df_merged, df_products
        
    except FileNotFoundError:
        print("Error: File data tidak ditemukan. Pastikan script dijalankan dari folder 'php-shoe-recommender'.")
        return None, None

def create_user_item_matrix(df):
    """
    Membuat Matrix User-Item (Pivot Table).
    Baris = User, Kolom = Item (Product Name), Nilai = Rating.
    """
    print("Creating User-Item Matrix...")
    # Pivot table: User sebagai index, Product Name sebagai columns, Rating sebagai values
    # Kita isi NaN dengan 0 (untuk sparse matrix)
    user_item_matrix = df.pivot_table(index='user_id', columns='product_name', values='rating').fillna(0)
    
    print(f"Matrix Shape: {user_item_matrix.shape}")
    return user_item_matrix

def train_svd_model(matrix, n_components=12):
    """
    Melakukan Matrix Factorization menggunakan SVD (Singular Value Decomposition).
    Ini mereduksi dimensi data untuk menangkap 'latent features'.
    """
    print(f"Training SVD Model (n_components={n_components})...")
    
    # Transpose matrix agar menjadi Item-User Matrix (Baris=Item)
    # Ini memudahkan kita mencari korelasi antar Item
    X = matrix.T
    
    # Inisialisasi TruncatedSVD
    SVD = TruncatedSVD(n_components=n_components, random_state=17)
    
    # Fit & Transform
    result_matrix = SVD.fit_transform(X)
    
    print(f"SVD Result Shape: {result_matrix.shape}")
    
    # Hitung Korelasi Pearson antar Item berdasarkan Latent Features
    print("Calculating Correlation Matrix...")
    corr_mat = np.corrcoef(result_matrix)
    
    return corr_mat, matrix.columns

def get_recommendations(product_name, corr_mat, product_names, top_n=5):
    """
    Mendapatkan rekomendasi produk yang mirip berdasarkan korelasi SVD.
    """
    try:
        product_names_list = list(product_names)
        product_idx = product_names_list.index(product_name)
        
        # Ambil vektor korelasi untuk produk tersebut
        correlation_vector = corr_mat[product_idx]
        
        # Filter: Hanya ambil yang korelasinya tinggi (mirip)
        # Kurang dari 1.0 agar tidak merekomendasikan diri sendiri
        
        recommend_indices = correlation_vector.argsort()[-top_n-1:-1][::-1]
        
        print(f"\n--- Recommendations based on '{product_name}' ---")
        for idx in recommend_indices:
            rec_product = product_names_list[idx]
            score = correlation_vector[idx]
            print(f"- {rec_product} (Score: {score:.4f})")
            
    except ValueError:
        print(f"\nProduct '{product_name}' not found in matrix.")

# --- MAIN EXECUTION FLOW ---

if __name__ == "__main__":
    df, df_products = load_data()
    
    if df is not None:
        # 1. Buat Matrix
        matrix_user_item = create_user_item_matrix(df)
        
        # 2. Latih Model SVD & Hitung Korelasi
        # n_components bisa disesuaikan, misal min(jumlah_item-1, 12)
        n_comp = min(len(matrix_user_item.columns) - 1, 12) 
        corr_matrix, product_names = train_svd_model(matrix_user_item, n_components=12)
        
        # 3. Contoh Rekomendasi
        # Ambil satu produk acak yang populer untuk dijadikan contoh input
        sample_prod = df['product_name'].mode()[0] 
        get_recommendations(sample_prod, corr_matrix, product_names)
        
        # Ambil satu lagi contoh
        if len(product_names) > 10:
             sample_prod_2 = list(product_names)[10]
             get_recommendations(sample_prod_2, corr_matrix, product_names)
