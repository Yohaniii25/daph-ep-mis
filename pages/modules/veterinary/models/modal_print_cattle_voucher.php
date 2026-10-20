<!-- Modal: Official Printable Cattle Voucher & Counterfoil (Two-Part Document) -->
<div class="modal fade" id="modalPrintCattleVoucher" tabindex="-1" aria-labelledby="modalPrintCattleVoucherLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <!-- Modal Header (Screen Only) -->
            <div class="modal-header bg-dark text-white py-2 px-4 d-print-none shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-printer-fill fs-5 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-white" id="modalPrintCattleVoucherLabel">
                            Printable Cattle Voucher &amp; Counterfoil (Animal Birth Certificate)
                        </h6>
                        <small class="text-white-50">Official Two-Part Statutory Document: Office Counterfoil Stub (Left) &amp; Main Voucher (Right)</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary font-monospace" id="printPreviewSerialBadge">Serial: —</span>
                    <button type="button" class="btn btn-warning btn-sm text-dark fw-bold px-3 shadow-sm" onclick="printCattleVoucherDocument();">
                        <i class="bi bi-printer me-1"></i>Print / Save as PDF
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- Modal Body (Contains Printable Two-Part Certificate) -->
            <div class="modal-body p-2 p-md-4 bg-secondary bg-opacity-10" id="cattleVoucherPrintWrapper">
                
                <!-- PRINT DOCUMENT WRAPPER (White Sheet) -->
                <div class="schedule06-print-sheet mx-auto p-3 p-md-4 bg-white shadow-sm border" id="cattleVoucherPrintSheet">
                    
                    <div class="row g-0 align-items-stretch">
                        
                        <!-- ============================================================== -->
                        <!-- PART 1: THE COUNTERFOIL (RECEIPT / OFFICE STUB)                -->
                        <!-- ============================================================== -->
                        <div class="col-12 col-lg-4 pe-lg-3 counterfoil-column">
                            <div class="counterfoil-box h-100 p-3 border border-dark rounded-1 position-relative" style="background-color: #fafbfc;">
                                
                                <!-- Counterfoil Watermark Header -->
                                <div class="text-center border-bottom border-dark pb-2 mb-2">
                                    <div class="badge bg-dark text-white font-monospace px-2 py-1 mb-1" style="font-size: 0.7rem;">
                                        SCHEDULE - 06 : COUNTERFOIL (STUB)
                                    </div>
                                    <h6 class="fw-bold text-uppercase mb-0 text-dark" style="font-size: 0.78rem; letter-spacing: 0.5px;">
                                        GOVERNMENT VETERINARY OFFICE COPY
                                    </div>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">Department of Animal Production &amp; Health</small>
                                    <div class="mt-1">
                                        <span class="fw-bold text-danger font-monospace" style="font-size: 0.85rem;" id="prt_cf_serial">APH/CV/—</span>
                                    </div>
                                </div>

                                <!-- Counterfoil: 01. Details of Owner -->
                                <div class="mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                                    <div class="fw-bold text-uppercase text-dark mb-1" style="font-size: 0.7rem; color: #370709;">
                                        01. Details of the Owner
                                    </div>
                                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.72rem; line-height: 1.3;">
                                        <tr>
                                            <td class="text-muted p-0" style="width: 75px;">Name:</td>
                                            <td class="p-0"><strong class="text-dark" id="prt_cf_owner_name">—</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-0">NIC No:</td>
                                            <td class="p-0 font-monospace fw-semibold text-dark" id="prt_cf_nic">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-0 align-top">Address:</td>
                                            <td class="p-0 text-dark" id="prt_cf_address">—</td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Counterfoil: 02. Animal / Farm Holding -->
                                <div class="mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                                    <div class="fw-bold text-uppercase text-dark mb-1" style="font-size: 0.7rem; color: #370709;">
                                        02. Holding Particulars
                                    </div>
                                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.72rem; line-height: 1.3;">
                                        <tr>
                                            <td class="text-muted p-0" style="width: 75px;">Farm Reg:</td>
                                            <td class="p-0 font-monospace fw-semibold" id="prt_cf_farm_reg">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-0">VS Area:</td>
                                            <td class="p-0" id="prt_cf_vs_area">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-0">DS Area:</td>
                                            <td class="p-0" id="prt_cf_ds_area">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-0">District:</td>
                                            <td class="p-0" id="prt_cf_district">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-0">G.N. Div:</td>
                                            <td class="p-0" id="prt_cf_gn_div">—</td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Counterfoil: 03. Animal Description -->
                                <div class="mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                                    <div class="fw-bold text-uppercase text-dark mb-1" style="font-size: 0.7rem; color: #370709;">
                                        03. Description of Animal
                                    </div>
                                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.72rem; line-height: 1.3;">
                                        <tr>
                                            <td class="text-muted p-0" style="width: 75px;">ID / Tag No:</td>
                                            <td class="p-0"><strong class="font-monospace text-success" id="prt_cf_tag_no">—</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-0">Species:</td>
                                            <td class="p-0" id="prt_cf_species">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-0">Breed &amp; Sex:</td>
                                            <td class="p-0"><span id="prt_cf_breed">—</span> (<span id="prt_cf_sex">—</span>)</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-0">DOB / Age:</td>
                                            <td class="p-0"><span id="prt_cf_dob">—</span> / <span id="prt_cf_age">—</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-0">Colour:</td>
                                            <td class="p-0" id="prt_cf_colour">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-0 align-top">Marks:</td>
                                            <td class="p-0" style="font-size: 0.68rem;" id="prt_cf_marks">—</td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Counterfoil: 04. Signatures & Authorizations -->
                                <div class="mt-2">
                                    <div class="fw-bold text-uppercase text-dark mb-1" style="font-size: 0.7rem; color: #370709;">
                                        04. Signatures &amp; Issue
                                    </div>
                                    <div class="row g-2 mt-1" style="font-size: 0.68rem;">
                                        <div class="col-6 text-center">
                                            <div class="p-2 border border-secondary border-dashed rounded text-muted" style="min-height: 52px;">
                                                <small class="d-block" style="font-size: 0.62rem;">Owner Thumb / Sig</small>
                                            </div>
                                        </div>
                                        <div class="col-6 text-center">
                                            <div class="p-2 border border-secondary border-dashed rounded text-muted" style="min-height: 52px;">
                                                <small class="d-block" style="font-size: 0.62rem;">VS Official Seal</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2 small" style="font-size: 0.68rem;">
                                        <span class="text-muted">Issue Date: <strong class="text-dark" id="prt_cf_date">—</strong></span>
                                        <span class="text-muted">Status: <strong class="text-success" id="prt_cf_status">Active</strong></span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- ============================================================== -->
                        <!-- VERTICAL PERFORATED LINE SEPARATOR                             -->
                        <!-- ============================================================== -->
                        <div class="d-none d-lg-flex flex-column align-items-center justify-content-center px-1 perforation-line" style="width: 28px;">
                            <div class="h-100 border-start border-2 border-dashed border-secondary my-1 position-relative d-flex align-items-center justify-content-center" style="width: 2px;">
                                <span class="badge bg-light text-muted border px-1 py-2 text-uppercase rotate-text" style="writing-mode: vertical-rl; transform: rotate(180deg); font-size: 0.62rem; letter-spacing: 2px;">
                                    ✂ PERFORATE HERE (TEAR OFF STUB)
                                </span>
                            </div>
                        </div>

                        <!-- ============================================================== -->
                        <!-- PART 2: THE MAIN CATTLE VOUCHER (ANIMAL BIRTH CERTIFICATE)     -->
                        <!-- ============================================================== -->
                        <div class="col-12 col-lg-8 ps-lg-3 voucher-column">
                            <div class="voucher-box h-100 p-3 p-md-4 border border-2 border-dark rounded-1 position-relative" style="background-color: #ffffff;">
                                
                                <!-- Decorative Corner Seals / Border Pattern -->
                                <div class="position-absolute top-0 start-0 m-2 text-muted opacity-25 d-none d-sm-block">
                                    <i class="bi bi-shield-fill fs-4"></i>
                                </div>
                                <div class="position-absolute top-0 end-0 m-2 text-muted opacity-25 d-none d-sm-block">
                                    <i class="bi bi-shield-fill fs-4"></i>
                                </div>

                                <!-- OFFICIAL STATUTORY HEADER -->
                                <div class="text-center pb-3 mb-3 border-bottom border-dark border-2">
                                    <div class="text-uppercase fw-bold text-dark mb-1" style="font-size: 0.78rem; letter-spacing: 1px;">
                                        Democratic Socialist Republic of Sri Lanka
                                    </div>
                                    <h4 class="fw-bold text-uppercase mb-1" style="color: #370709; letter-spacing: 0.5px; font-size: 1.25rem;">
                                        DEPARTMENT OF ANIMAL PRODUCTION AND HEALTH
                                    </h4>
                                    <div class="d-flex align-items-center justify-content-center gap-2 mb-1 flex-wrap">
                                        <span class="badge bg-dark text-white font-monospace px-3 py-1 fs-6">
                                            SCHEDULE - 06
                                        </span>
                                        <h5 class="fw-bold text-uppercase text-decoration-underline mb-0 text-dark" style="font-size: 1.05rem;">
                                            Cattle Voucher &amp; Official Animal Birth Certificate
                                        </h5>
                                    </div>
                                    <small class="text-muted d-block fst-italic" style="font-size: 0.72rem;">
                                        Prescribed under Section 4 of the Animals Act &amp; National Livestock Identification &amp; Traceability Regulations
                                    </small>

                                    <!-- Unique Serial Number Banner -->
                                    <div class="d-inline-flex align-items-center border border-2 border-dark rounded px-3 py-1 mt-2 bg-light shadow-xs">
                                        <span class="small text-uppercase fw-bold text-muted me-2 font-monospace" style="font-size: 0.75rem;">Voucher Serial No:</span>
                                        <strong class="font-monospace fs-5 text-danger" id="prt_main_serial">APH/CV/—</strong>
                                    </div>
                                </div>

                                <!-- SECTION 01: DETAILS OF THE OWNER -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center gap-2 mb-1 py-1 px-2 text-white rounded-1" style="background-color: #370709;">
                                        <span class="badge bg-warning text-dark font-monospace fw-bold">01</span>
                                        <h6 class="fw-bold mb-0 text-uppercase" style="font-size: 0.82rem; letter-spacing: 0.5px;">Details of the Owner</h6>
                                    </div>
                                    <table class="table table-sm table-bordered border-dark mb-0 align-middle" style="font-size: 0.8rem;">
                                        <tbody>
                                            <tr>
                                                <th class="table-light ps-2" style="width: 25%;">Name of the Owner:</th>
                                                <td class="ps-2" colspan="3">
                                                    <span class="text-muted fw-semibold me-1" id="prt_main_title">Mr.</span>
                                                    <strong class="text-dark fs-6" id="prt_main_owner_name">—</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="table-light ps-2">National Identity Card (NIC):</th>
                                                <td class="ps-2 font-monospace fw-bold text-primary" style="width: 28%;" id="prt_main_nic">—</td>
                                                <th class="table-light ps-2" style="width: 18%;">Contact Phone:</th>
                                                <td class="ps-2 font-monospace" id="prt_main_phone">—</td>
                                            </tr>
                                            <tr>
                                                <th class="table-light ps-2">Permanent Address:</th>
                                                <td class="ps-2" colspan="3" id="prt_main_address">—</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- SECTION 02: DETAILS OF THE ANIMAL / FARM HOLDING -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center gap-2 mb-1 py-1 px-2 text-white rounded-1" style="background-color: #370709;">
                                        <span class="badge bg-warning text-dark font-monospace fw-bold">02</span>
                                        <h6 class="fw-bold mb-0 text-uppercase" style="font-size: 0.82rem; letter-spacing: 0.5px;">Details of the Animal / Farm Holding</h6>
                                    </div>
                                    <table class="table table-sm table-bordered border-dark mb-0 align-middle" style="font-size: 0.8rem;">
                                        <tbody>
                                            <tr>
                                                <th class="table-light ps-2" style="width: 25%;">Province:</th>
                                                <td class="ps-2" style="width: 25%;" id="prt_main_province">Eastern Province</td>
                                                <th class="table-light ps-2" style="width: 25%;">District:</th>
                                                <td class="ps-2" style="width: 25%;" id="prt_main_district">—</td>
                                            </tr>
                                            <tr>
                                                <th class="table-light ps-2">Divisional Secretariat (DS) Area:</th>
                                                <td class="ps-2" id="prt_main_ds_area">—</td>
                                                <th class="table-light ps-2">Govt. Veterinary Surgeon Area:</th>
                                                <td class="ps-2 fw-semibold" id="prt_main_vs_area">—</td>
                                            </tr>
                                            <tr>
                                                <th class="table-light ps-2">Farm Registration Number:</th>
                                                <td class="ps-2 font-monospace fw-bold text-dark" id="prt_main_farm_reg">—</td>
                                                <th class="table-light ps-2">G.N. Division &amp; Number:</th>
                                                <td class="ps-2" id="prt_main_gn_div">—</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- SECTION 03: DESCRIPTION OF THE ANIMAL (ANIMAL BIRTH PARTICULARS) -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center gap-2 mb-1 py-1 px-2 text-white rounded-1" style="background-color: #370709;">
                                        <span class="badge bg-warning text-dark font-monospace fw-bold">03</span>
                                        <h6 class="fw-bold mb-0 text-uppercase" style="font-size: 0.82rem; letter-spacing: 0.5px;">
                                            Description of the Animal (Statutory Birth Certificate Particulars)
                                        </h6>
                                    </div>
                                    <table class="table table-sm table-bordered border-dark mb-0 align-middle text-start" style="font-size: 0.8rem;">
                                        <tbody>
                                            <tr>
                                                <th class="table-light ps-2" style="width: 25%;">Animal Identification Number:</th>
                                                <td class="ps-2" style="width: 25%;">
                                                    <strong class="font-monospace text-success fs-6" id="prt_main_tag_no">—</strong>
                                                </td>
                                                <th class="table-light ps-2" style="width: 25%;">Date of Identification:</th>
                                                <td class="ps-2 font-monospace" style="width: 25%;" id="prt_main_id_date">—</td>
                                            </tr>
                                            <tr>
                                                <th class="table-light ps-2">Species of Animal:</th>
                                                <td class="ps-2" id="prt_main_species">Neat Cattle</td>
                                                <th class="table-light ps-2">Breed:</th>
                                                <td class="ps-2 fw-semibold" id="prt_main_breed">—</td>
                                            </tr>
                                            <tr>
                                                <th class="table-light ps-2">Sex:</th>
                                                <td class="ps-2 fw-bold" id="prt_main_sex">—</td>
                                                <th class="table-light ps-2">Colour / Coat Pattern:</th>
                                                <td class="ps-2" id="prt_main_colour">—</td>
                                            </tr>
                                            <tr>
                                                <th class="table-light ps-2">Date of Birth (Animal DOB):</th>
                                                <td class="ps-2 font-monospace fw-bold text-dark" id="prt_main_dob">—</td>
                                                <th class="table-light ps-2">Age (at Certification):</th>
                                                <td class="ps-2" id="prt_main_age">—</td>
                                            </tr>
                                            <tr>
                                                <th class="table-light ps-2">Specific Marks &amp; Characteristics:</th>
                                                <td class="ps-2" colspan="3" id="prt_main_marks">—</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- SECTION 04: SIGNATURES & AUTHORIZATIONS -->
                                <div class="mb-2">
                                    <div class="d-flex align-items-center gap-2 mb-1 py-1 px-2 text-white rounded-1" style="background-color: #370709;">
                                        <span class="badge bg-warning text-dark font-monospace fw-bold">04</span>
                                        <h6 class="fw-bold mb-0 text-uppercase" style="font-size: 0.82rem; letter-spacing: 0.5px;">Signatures &amp; Authorizations</h6>
                                    </div>
                                    
                                    <div class="row g-2 pt-2 text-center align-items-end" style="font-size: 0.76rem;">
                                        <!-- Owner Signature Box -->
                                        <div class="col-4">
                                            <div class="p-2 border border-dark rounded text-center bg-light" style="min-height: 80px;">
                                                <div class="border-bottom border-dark border-dashed my-3"></div>
                                                <p class="mb-0 fw-bold text-dark">Signature / Thumb Mark of Owner</p>
                                                <small class="text-muted d-block">Title Holder Signature</small>
                                            </div>
                                        </div>

                                        <!-- Issue Date Box -->
                                        <div class="col-3">
                                            <div class="p-2 border border-dark rounded text-center bg-light" style="min-height: 80px;">
                                                <div class="mt-3">
                                                    <span class="small text-muted d-block">Date of Certification:</span>
                                                    <strong class="font-monospace fs-6 text-dark" id="prt_main_date">—</strong>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- VS Stamp & Seal Box -->
                                        <div class="col-5">
                                            <div class="p-2 border border-2 border-dark rounded text-center bg-light position-relative" style="min-height: 80px;">
                                                <div class="border-bottom border-dark border-dashed my-3"></div>
                                                <strong class="d-block text-dark" id="prt_main_vs_name">Government Veterinary Surgeon</strong>
                                                <small class="text-muted d-block">Official Stamp &amp; Signature</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statutory Legal Footer -->
                                <div class="mt-3 pt-2 border-top border-dark text-center text-muted" style="font-size: 0.68rem; line-height: 1.3;">
                                    <p class="mb-0">
                                        <strong>STATUTORY CERTIFICATION:</strong> This Schedule-06 Cattle Voucher serves as the official Animal Birth Certificate &amp; Legal Title under Section 4 of the Animals Act. Any transfer, sale, change of location, slaughter, or death of this animal must be endorsed hereon and notified immediately to the Government Veterinary Surgeon.
                                    </p>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Modal Footer (Screen Only) -->
            <div class="modal-footer bg-light py-2 px-4 justify-content-between d-print-none border-top">
                <div class="small text-muted">
                    <i class="bi bi-info-circle me-1"></i>Standard A4 Landscape print format is recommended for printing both Counterfoil Stub &amp; Main Voucher side-by-side.
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-dark btn-sm px-4 fw-bold shadow-sm" onclick="printSchedule06Document();">
                        <i class="bi bi-printer me-1"></i>Print Document
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Stylesheet for Schedule - 06 Certificate -->
<style>
@media print {
    /* Hide everything on page except modal print area */
    body * {
        visibility: hidden !important;
    }
    #cattleVoucherPrintWrapper,
    #cattleVoucherPrintWrapper * {
        visibility: visible !important;
    }
    #cattleVoucherPrintWrapper {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background-color: #ffffff !important;
    }
    #cattleVoucherPrintSheet {
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 5mm !important;
        margin: 0 !important;
    }
    .modal {
        position: static !important;
        overflow: visible !important;
    }
    .modal-dialog {
        margin: 0 !important;
        max-width: 100% !important;
    }
    .d-print-none {
        display: none !important;
    }
    @page {
        size: A4 landscape;
        margin: 8mm;
    }
}

.rotate-text {
    display: inline-block;
}
</style>
