<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Spatie\PdfToImage\Pdf;
use Illuminate\Support\Facades\Log;

class PdfController extends Controller
{
    public function convertPdfToText(Request $request)
    {
        ini_set('memory_limit', '1024M'); // Increase memory limit
        ini_set('max_execution_time', '0'); // Allow unlimited execution time

        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:307200', // Limit file size to 300MB
        ]);

        try {
            $popplerBin = 'E:\poppler\poppler-24.07.0\Library\bin'; // Adjust this to your actual Poppler bin path
            $tesseractPath = 'E:\ocr'; // Path to Tesseract
            putenv("PATH=$popplerBin;$tesseractPath;" . getenv('PATH'));

            $pdfFile = $request->file('pdf_file');

            // Sanitize the file name: remove spaces and special characters
            $sanitizedFileName = Str::slug(pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME));
            $sanitizedFileName .= '.' . $pdfFile->getClientOriginalExtension();

            // Store the file in the public/pdf_text directory
            $pdfFilePath = public_path('pdf_text/' . $sanitizedFileName);
            $pdfFile->move(public_path('pdf_text'), $sanitizedFileName);

            $imageOutputDir = dirname($pdfFilePath); // Set the output directory to where the PDF is uploaded

            session()->put('image_conversion_progress', 0);
            session()->put('text_conversion_progress', 0);

            $text = $this->convertScannedPdfToText($pdfFilePath, $imageOutputDir);

            // Save the extracted text to a .txt file in the public/pdf_text directory
            $textFileName = pathinfo($sanitizedFileName, PATHINFO_FILENAME) . '.txt';
            $textFilePath = public_path('pdf_text/' . $textFileName);
            file_put_contents($textFilePath, $text);

            // Return the path of the saved text file as part of the JSON response
            $textFileUrl = url('pdf_text/' . $textFileName);
            return response()->json(['text_file_url' => $textFileUrl]);
        } catch (\Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function convertScannedPdfToText($pdfPath, $outputDir)
    {
        try {
            if (!file_exists($outputDir)) {
                if (!mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $outputDir));
                }
            }

            // Ensure output directory path does not have a trailing slash
            $outputDir = rtrim($outputDir, DIRECTORY_SEPARATOR);

            // Base name for the output files
            $outputBaseName = $outputDir . DIRECTORY_SEPARATOR . 'page';

            // Construct the command properly, handling spaces and special characters
            $command = escapeshellcmd("pdftoppm -png") . " " . escapeshellarg($pdfPath) . " " . escapeshellarg($outputBaseName) . " 2>&1";
            
            exec($command, $output, $return_var);

            Log::info("Command: $command");
            Log::info("Command output: " . implode("\n", $output));
            Log::info("Return status: $return_var");

            if ($return_var !== 0) {
                throw new \Exception("pdftoppm command failed with status $return_var. Output: " . implode("\n", $output));
            }

            // Update progress for image conversion
            session()->put('image_conversion_progress', 100);

            $text = '';

            $totalImages = count(glob("$outputBaseName*.png"));
            $currentImage = 0;

            $imagePaths = glob("$outputBaseName*.png");

            // Process images in parallel
            $chunks = array_chunk($imagePaths, ceil($totalImages / 4)); // 4 parallel processes

            foreach ($chunks as $chunk) {
                $texts = [];

                foreach ($chunk as $imagePath) {
                    $ocr = new TesseractOCR($imagePath);
                    $ocr->executable('E:\ocr\tesseract.exe'); // Explicitly set the path to Tesseract
                    $ocr->timeout(0); // Set Tesseract to have no timeout
                    
                    $texts[] = $ocr->run();

                    // Update progress for text conversion
                    $currentImage++;
                    session()->put('text_conversion_progress', ($currentImage / $totalImages) * 100);
                }

                $text .= implode("\n", $texts);
            }

            return $text;
        } catch (\Exception $e) {
            Log::error('An error occurred during OCR processing: ' . $e->getMessage());
            throw $e; // Re-throw the exception to be handled by the caller
        }
    }
}
