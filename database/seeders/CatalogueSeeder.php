<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CatalogueSeeder extends Seeder
{
    /**
     * Sample books: [title, author, ISBN, category, shelf, copies].
     *
     * @var list<array{string, string, string, string, string, int}>
     */
    private const array BOOKS = [
        ['Things Fall Apart', 'Chinua Achebe', '9780385474542', 'Fiction', 'F-01', 3],
        ['The River Between', 'Ngũgĩ wa Thiong\'o', '9780435905484', 'Fiction', 'F-02', 2],
        ['A Grain of Wheat', 'Ngũgĩ wa Thiong\'o', '9780141186993', 'Fiction', 'F-02', 2],
        ['Half of a Yellow Sun', 'Chimamanda Ngozi Adichie', '9781400095209', 'Fiction', 'F-03', 2],
        ['The River and the Source', 'Margaret Ogola', '9789966882059', 'Fiction', 'F-04', 4],
        ['Blossoms of the Savannah', 'Henry Ole Kulet', '9789966360373', 'Fiction', 'F-05', 3],
        ['Introduction to Algorithms', 'Thomas H. Cormen', '9780262046305', 'Computer Science', 'CS-01', 2],
        ['Clean Code', 'Robert C. Martin', '9780132350884', 'Computer Science', 'CS-02', 1],
        ['Software Engineering', 'Ian Sommerville', '9780133943030', 'Computer Science', 'CS-03', 3],
        ['Database System Concepts', 'Abraham Silberschatz', '9780078022159', 'Computer Science', 'CS-04', 2],
        ['Computer Networking: A Top-Down Approach', 'James Kurose', '9780133594140', 'Computer Science', 'CS-05', 1],
        ['Calculus: Early Transcendentals', 'James Stewart', '9781285741550', 'Mathematics', 'M-01', 3],
        ['Linear Algebra and Its Applications', 'David C. Lay', '9780321982384', 'Mathematics', 'M-02', 2],
        ['Discrete Mathematics and Its Applications', 'Kenneth Rosen', '9780073383095', 'Mathematics', 'M-03', 2],
        ['A Brief History of Time', 'Stephen Hawking', '9780553380163', 'Science', 'S-01', 2],
        ['The Selfish Gene', 'Richard Dawkins', '9780198788607', 'Science', 'S-02', 1],
        ['Campbell Biology', 'Lisa Urry', '9780134093413', 'Science', 'S-03', 2],
        ['Facing Mount Kenya', 'Jomo Kenyatta', '9780394702100', 'History', 'H-01', 2],
        ['Unbowed: A Memoir', 'Wangari Maathai', '9780307275202', 'History', 'H-02', 2],
        ['Long Walk to Freedom', 'Nelson Mandela', '9780316548182', 'History', 'H-03', 1],
        ['Principles of Economics', 'N. Gregory Mankiw', '9781305585126', 'Business', 'B-01', 3],
        ['Financial Accounting', 'Jerry Weygandt', '9781119594598', 'Business', 'B-02', 2],
    ];

    public function run(): void
    {
        foreach (self::BOOKS as [$title, $author, $isbn, $category, $shelf, $copies]) {
            Book::withTrashed()->firstOrCreate(['isbn' => $isbn], [
                'title' => $title,
                'author' => $author,
                'category_id' => Category::firstOrCreate(['name' => $category])->id,
                'shelf_location' => $shelf,
                'total_copies' => $copies,
            ]);
        }
    }
}
