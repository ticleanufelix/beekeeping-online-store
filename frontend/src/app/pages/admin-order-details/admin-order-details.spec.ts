import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AdminOrderDetails } from './admin-order-details';

describe('AdminOrderDetails', () => {
  let component: AdminOrderDetails;
  let fixture: ComponentFixture<AdminOrderDetails>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AdminOrderDetails],
    }).compileComponents();

    fixture = TestBed.createComponent(AdminOrderDetails);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
