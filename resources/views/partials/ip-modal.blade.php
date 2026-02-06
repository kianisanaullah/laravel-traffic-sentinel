{{-- IP Details Modal (Traffic Sentinel) --}}
<div class="modal fade" id="tsIpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content ts-ip-modal shadow-lg border-0 overflow-hidden">

            <div class="ts-ip-modal-head px-4 py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="ts-ip-flag" id="tsIpModalFlag">🌐</div>
                    <div>
                        <div class="ts-ip-title" id="tsIpModalTitle">IP Details</div>
                        <div class="ts-ip-sub small">
                            <span class="badge rounded-pill text-bg-dark-subtle border"
                                  id="tsIpModalCountryChip">—</span>
                            <span class="badge rounded-pill text-bg-dark-subtle border ms-2"
                                  id="tsIpModalAsnChip">—</span>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-body p-4">

                <div id="tsIpModalLoading" class="py-4 text-center text-muted">
                    <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                    Loading IP details...
                </div>

                <div id="tsIpModalError" class="alert alert-warning mb-0 d-none"></div>

                <div id="tsIpModalBody" class="d-none">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">IP</div>
                            <div class="d-flex align-items-center gap-2">
                                <code class="fs-6" id="tsIpModalIp">—</code>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="tsCopyIpBtn">
                                    <i class="bi bi-clipboard me-1"></i>Copy
                                </button>
                            </div>
                            <div class="text-success small mt-1 d-none" id="tsCopyOk">
                                <i class="bi bi-check2-circle me-1"></i>Copied
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Country</div>
                            <div class="fw-semibold" id="tsIpModalCountry">—</div>
                            <div class="text-muted small" id="tsIpModalCountryCode"></div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small mb-1">ASN</div>
                            <div class="fw-semibold" id="tsIpModalAsn">—</div>
                            <div class="text-muted small" id="tsIpModalAsnCountry"></div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small mb-1">CIDR Match</div>
                            <div class="fw-semibold" id="tsIpModalCidr">—</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-sm btn-outline-primary" target="_blank" id="tsOpenWhoisLink" href="#">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open Whois
                        </a>

                        <a class="btn btn-sm btn-outline-secondary" target="_blank" id="tsOpenIpLink" href="#">
                            <i class="bi bi-globe2 me-1"></i>Open IP
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .ts-ip-modal { border-radius: 18px; }
    .ts-ip-modal-head{
        background: linear-gradient(135deg, rgba(99, 102, 241, .14), rgba(16, 185, 129, .10));
        border-bottom: 1px solid rgba(255, 255, 255, .08);
    }
    [data-bs-theme="light"] .ts-ip-modal-head{
        border-bottom: 1px solid rgba(15, 23, 42, .08);
    }
    .ts-ip-flag{
        width: 44px; height: 44px;
        display: grid; place-items: center;
        font-size: 26px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, .14);
        background: rgba(255, 255, 255, .06);
    }
    [data-bs-theme="light"] .ts-ip-flag{
        border-color: rgba(15, 23, 42, .10);
        background: rgba(15, 23, 42, .03);
    }
    .ts-ip-title{ font-weight: 800; font-size: 1.05rem; line-height: 1.1; }
    .ts-ip-sub .badge{ font-weight: 600; }
</style>
