@page {
    size: A6 landscape;
    margin: 10px;
}
html, body {
    font-family: "Arial", sans-serif;
    font-size: 11px;
    margin: 6px 6px;
    padding: 8px 15px;
}

.left-section img {
    width: 70px;
    margin-right: 0;
    height: auto;
    margin-bottom: 5px;
}

/* Bagian kanan: isi kwitansi */
.right-section {
    width: 100%;
}

table {
    width: 100%;
    font-size: 11px;
}

.label {
    width: 120px;
    vertical-align: top;
}

.value-line {
    border-bottom: 1px dotted #000;
    display: inline-block;
    width: 100%;
}

.amount-box {
    border: 1px solid green;
    padding: 3px 0;
    margin: 8px 0 0 0;
    font-weight: bold;
    text-align: center;
    width: 100%;
}

.ttd {
    margin-top: 0px;
    width: 100%;
}

.ttd td {
    text-align: center;
    font-size: 11px;
}
.body-text {
    font-size: 10px;
}
.watermark {
    position: fixed;            /* stay in place for all pages */
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 60%;                 /* adjust size as needed */
    opacity: 0.06;              /* watermark transparency */
    /* z-index is not required in DomPDF; draw order handles stacking */
}
