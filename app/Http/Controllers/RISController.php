<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use Illuminate\Routing\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RISController extends Controller
{
    /**
     * Build FULL NAME (UPPERCASE) safely (no extra spaces).
     */
    private function fullName($profile): string
    {
        if (!$profile) return "";

        // If you have accessor full_name on UserProfile, prefer it.
        $name = $profile->full_name
            ?? trim(
                implode(" ", array_filter([
                    $profile->first_name ?? null,
                    $profile->middle_name ?? null,
                    $profile->last_name ?? null,
                    $profile->suffix ?? null,
                ]))
            );

        $name = preg_replace('/\s+/', ' ', trim((string) $name));
        return strtoupper($name);
    }

    /**
     * Build POSITION TITLE (UPPERCASE) safely.
     * IMPORTANT: In your schema, position is a relationship via position_id,
     * so the title is $profile->position->position (NOT $profile->position).
     */
    private function positionTitle($profile): string
    {
        if (!$profile) return "";

        // If API returns designation sometimes, allow fallback
        $pos =
            // Relationship: Position model field "position"
            ($profile->position->position ?? null)
            // Fallbacks in case you stored text somewhere
            ?? ($profile->designation ?? null)
            ?? ($profile->position_name ?? null)
            ?? "";

        $pos = preg_replace('/\s+/', ' ', trim((string) $pos));
        return strtoupper($pos);
    }

    public function exportRIS($id)
    {
        /**
         * ✅ Load checkout with needed relations
         * NOTE: must eager-load position relation INSIDE userProfile/approvedBy/issuedBy
         * so we can use $profile->position->position safely.
         */
        $checkout = Checkout::with([
            'items.product',
            'userProfile.position',
            'approvedBy.position',
            'issuedBy.position',
        ])->findOrFail($id);

        /** Load template */
        $template = public_path('ris/ris_template.xlsx');
        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();

        /* ----------------------------------------------------------------------
            RIS NUMBER (H6)  → YEAR + MONTH + CHECKOUT ID  e.g., 2026-01-44
        -----------------------------------------------------------------------*/
        $risNo = date("Y-m") . "-" . $checkout->id;
        $sheet->setCellValue("H6", $risNo);

        /* ----------------------------------------------------------------------
            REQUESTER DIVISION (C5)
        -----------------------------------------------------------------------*/
        $sheet->setCellValue("C5", $checkout->userProfile->division ?? "");

        /* ----------------------------------------------------------------------
            ITEMS LOOP (Start row 10)
            B = Unit
            C = Product Name
            D = Requested Qty
            E = YES check
            F = NO check
            G = Approved Qty
            H = Remarks
        -----------------------------------------------------------------------*/
        $startRow = 10;

        foreach ($checkout->items as $i => $item) {
            $row = $startRow + $i;

            $unit        = $item->unit ?? ($item->product->unit ?? "");
            $productName = $item->product->product_name ?? "";
            $qty         = $item->quantity ?? 0;
            $approvedQty = $item->approved_qty ?? 0;
            $isApproved  = ($item->status ?? "") === "Approved";

            $sheet->setCellValue("B{$row}", $unit);
            $sheet->setCellValue("C{$row}", $productName);
            $sheet->setCellValue("D{$row}", $qty);
            $sheet->setCellValue("E{$row}", $isApproved ? "✔" : "");
            $sheet->setCellValue("F{$row}", $isApproved ? "" : "✔");
            $sheet->setCellValue("G{$row}", $approvedQty);
            $sheet->setCellValue("H{$row}", $isApproved ? "Released" : "Not Available");
        }

        /* ----------------------------------------------------------------------
            DATE REQUESTED (C26, D26, E26, G26, H26)
            Format → d-M-Y  (07-Jan-2026)
        -----------------------------------------------------------------------*/
        $dateRequested = $checkout->created_at
            ? $checkout->created_at->format("d-M-Y")
            : date("d-M-Y");

        $sheet->setCellValue("C26", $dateRequested);
        $sheet->setCellValue("D26", $dateRequested);
        $sheet->setCellValue("E26", $dateRequested);
        $sheet->setCellValue("G26", $dateRequested);
        $sheet->setCellValue("H26", $dateRequested);

        /* ----------------------------------------------------------------------
            ✅ SIGNATORIES: ONLY FULL NAME + POSITION (NO JSON)
            Requested by (C24, C25)
            Approved by  (D24, D25)
            Issued by    (E24, E25)
            Received by  (G24, G25) -> same as requester
        -----------------------------------------------------------------------*/
        $req = $checkout->userProfile;

        $reqFull = $this->fullName($req);
        $reqPos  = $this->positionTitle($req);

        $sheet->setCellValue("C24", $reqFull);
        $sheet->setCellValue("C25", $reqPos);

        if ($checkout->approvedBy) {
            $aFull = $this->fullName($checkout->approvedBy);
            $aPos  = $this->positionTitle($checkout->approvedBy);

            $sheet->setCellValue("D24", $aFull);
            $sheet->setCellValue("D25", $aPos);
        } else {
            $sheet->setCellValue("D24", "");
            $sheet->setCellValue("D25", "");
        }

        if ($checkout->issuedBy) {
            $iFull = $this->fullName($checkout->issuedBy);
            $iPos  = $this->positionTitle($checkout->issuedBy);

            $sheet->setCellValue("E24", $iFull);
            $sheet->setCellValue("E25", $iPos);
        } else {
            $sheet->setCellValue("E24", "");
            $sheet->setCellValue("E25", "");
        }

        // Received by = requester
        $sheet->setCellValue("G24", $reqFull);
        $sheet->setCellValue("G25", $reqPos);

        /* ----------------------------------------------------------------------
            OUTPUT FILE
        -----------------------------------------------------------------------*/
        $writer = IOFactory::createWriter($spreadsheet, "Xlsx");

        return new StreamedResponse(function () use ($writer) {
            $writer->save("php://output");
        }, 200, [
            "Content-Type"        => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => 'attachment; filename="RIS.xlsx"',
        ]);
    }
}
